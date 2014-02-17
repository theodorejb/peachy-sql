<?php

/**
 * Provides simple methods for executing queries with bound parameters and 
 * inserting, selecting, updating, and deleting rows in a table. Supports both
 * MySQL (via mysqli) and T-SQL (via Microsoft's SQLSRV extension) and can be 
 * extended by classes for individual tables.
 *
 * @author Theodore Brown <https://github.com/theodorejb>
 * @version 0.8.0
 */
class PeachySQL {

    const DBTYPE_TSQL = 'tsql';
    const DBTYPE_MYSQL = 'mysql';

    /**
     * A mysqli or sqlsrv database connection
     * @var mixed
     */
    private $connection;

    /**
     * 'mysql' or 'tsql'
     * @var string
     */
    private $dbType;

    /**
     * The name of the table to query
     * @var string
     */
    private $tableName;

    /**
     * @param mixed  $connection A SQLSRV or mysqli database connection
     * @param string $dbType     The type of database being accessed ('tsql' or
     *                           'mysql')
     * @param string $tableName (optional) The name of the table to be queried.
     * @throws Exception if the database type is invalid.
     */
    public function __construct($connection, $dbType, $tableName = NULL) {
        $this->connection = $connection;
        $this->tableName = $tableName;

        if ($dbType === self::DBTYPE_MYSQL || $dbType === self::DBTYPE_TSQL) {
            $this->dbType = $dbType;
        } else {
            throw new Exception("Invalid database type");
        }
    }

    /**
     * Executes a single query and passes any errors, selected rows, and the 
     * number of affected rows to the callback function. Transactions are not 
     * supported. Errors are passed rather than thrown to support more flexible 
     * handling.
     * 
     * T-SQL only: supports multiple queries separated by semicolons.
     * MySQL only: If an INSERT or UPDATE query is performed on a table with an
     *             auto-incremented column, the $rows parameter passed to the
     *             callback will contain the insert ID of the first inserted row.
     * 
     * @param string   $sql
     * @param array    $params
     * @param callable $callback function (array $error, array $rows, int $affected)
     */
    public function query($sql, array $params, callable $callback) {
        if ($this->dbType === self::DBTYPE_TSQL) {
            $this->tsqlQuery($sql, $params, $callback);
        } else {
            $this->mysqlQuery($sql, $params, $callback);
        }
    }

    /**
     * Executes multiple queries separated by semicolons, and passes any errors,
     * selected rows, and the number of affected rows to the callback function. 
     * Transactions are not supported.
     * 
     * @param string   $sql
     * @param array    $params
     * @param callable $callback function (array $error, array $rows, int $affected)
     */
    private function tsqlQuery($sql, array $params, callable $callback) {
        $error = NULL;
        $rows = [];
        $affected = 0;

        if (!$stmt = sqlsrv_query($this->connection, $sql, $params)) {
            $error = sqlsrv_errors();
        } else {

            do {
                // get any selected rows
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $rows[] = $row;
                }

                $affectedRows = sqlsrv_rows_affected($stmt);

                if ($affectedRows > 0) {
                    $affected += $affectedRows;
                }
            } while ($nextResult = sqlsrv_next_result($stmt));

            if ($nextResult === FALSE) {
                $error = sqlsrv_errors();
            }

            sqlsrv_free_stmt($stmt);
        }

        $callback($error, $rows, $affected);
    }

    /**
     * Executes a single query and passes any errors, selected rows (or insert 
     * id if performing an insert/update), and the number of affected rows to 
     * the callback function. Transactions are not supported.
     * 
     * @param string   $sql
     * @param array    $params
     * @param callable $callback function (array $error, array $rows, int $affected)
     */
    private function mysqlQuery($sql, array $params, callable $callback) {
        $error = NULL;
        $rows = [];
        $affected = 0;

        // prepare the statement
        if (!$stmt = $this->connection->prepare($sql)) {
            $error = array("Failed to prepare statement", $this->connection->errno, $this->connection->error);
        } else {

            if (!empty($params)) {
                $typesValues = array(self::getMysqlParamTypes($params));

                for ($i = 0; $i < count($params); $i++) {
                    // use variable variables so that passing by reference will work
                    $bindName = 'bind' . $i;
                    $$bindName = $params[$i];
                    $typesValues[] = &$$bindName;
                }

                if (!call_user_func_array(array($stmt, 'bind_param'), $typesValues)) {
                    $error = array("Failed to bind parameters", $stmt->errno, $stmt->error);
                }
            }

            if ($error === NULL && !$stmt->execute()) {
                $error = array("Failed to execute prepared statement", $stmt->errno, $stmt->error);
            } else {
                $insertId = $stmt->insert_id; // id of first inserted row
                $stmt->store_result();
                $affected = $stmt->affected_rows;

                if ($insertId !== 0) {
                    // An insert or update statement was executed on a table
                    // with an auto-incremented column. Just return the insert ID.
                    $rows[] = $insertId;
                } else {

                    $variables = [];
                    $data = [];
                    $meta = $stmt->result_metadata();

                    if ($meta !== FALSE) {
                        // add a variable for each selected field
                        while ($field = $meta->fetch_field()) {
                            $variables[] = &$data[$field->name]; // pass by reference
                        }

                        if (!call_user_func_array(array($stmt, 'bind_result'), $variables)) {
                            $error = array("Failed to bind results", $stmt->errno, $stmt->error);
                        } else {
                            $i = 0;
                            while ($stmt->fetch()) {
                                // loop through all the fields and values to prevent
                                // PHP from just copying the same $data reference (see
                                // http://www.php.net/manual/en/mysqli-stmt.bind-result.php#92505).

                                $rows[$i] = [];
                                foreach ($data as $k => $v) {
                                    $rows[$i][$k] = $v;
                                }

                                $i++;
                            }
                        }
                    }
                }
            }

            $stmt->close();
        }

        $callback($error, $rows, $affected);
    }

    /**
     * @param string[] $columns  An array of columns to select (empty to select
     *                           all columns).
     * @param array    $where    An associative array of columns and values to
     *                           filter selected rows. E.g. ["id" => 3] to only
     *                           return rows where id is equal to 3.
     * @param callable $callback Same as query() callback
     */
    public function select(array $columns, array $where, callable $callback) {
        $query = self::buildSelectQuery($this->tableName, $this->dbType, $columns, $where);
        $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * Insert one or more rows into the table.
     * 
     * @param string[] $columns  The columns to be inserted into. E.g.
     *                           ["Username", "Password].
     * @param array    $values   An array containing one or more subarrays with
     *                           values for each column. E.g.
     *                           [ ["user1", "pass1"], ["user2", "pass2"] ]
     * @param string   $idCol    If specified, an array of insert IDs from this
     *                           column will be passed to the callback (has no 
     *                           effect when using mysql).
     * @param callable $callback function (array $errors, array $insertIDs, int $affected)
     */
    public function insert(array $columns, array $values, $idCol, callable $callback) {
        $query = self::buildInsertQuery($this->tableName, $this->dbType, $columns, $values, $idCol);
        $sql = $query["sql"];
        $params = $query["params"];

        $this->query($sql, $params, function ($err, $rows, $affected) use ($values, $callback) {
            if ($this->dbType === self::DBTYPE_TSQL) {
                // $rows contains an array of insert ID rows
                $ids = [];

                foreach ($rows as $row) {
                    $ids[] = $row["RowID"];
                }
            } else {
                // $rows contains the insert ID of the first inserted row
                $firstInsertId = $rows[0];
                $lastInsertId = $firstInsertId + (count($values) - 1);
                $ids = range($firstInsertId, $lastInsertId);
            }

            $callback($err, $ids, $affected);
        });
    }

    /**
     * Updates the specified columns and values in rows matching the where clause.
     * 
     * @param array $set   E.g. ["Username" => "newUsername", "Password" => "newPassword"]
     * @param array $where E.g. ["id" => 3] to update the row where id is equal to 3.
     * @param callable $callback Same as callback for query()
     */
    public function update(array $set, array $where, callable $callback) {
        $query = self::buildUpdateQuery($this->tableName, $this->dbType, $set, $where);
        $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * Deletes columns from the table where the where clause matches.
     * 
     * @param array    $where    E.g. ["id" => 3]
     * @param callable $callback function (array $errors, array $rows, array $affected)
     */
    public function delete(array $where, callable $callback) {
        $query = self::buildDeleteQuery($this->tableName, $this->dbType, $where);
        $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * If the array is empty, the query should select all rows. If not empty,
     * filter by the column values
     * 
     * @param string   $tableName The name of the table to query
     * @param string   $dbType    The database type ('mysql' or 'tsql')
     * @param string[] $columns   An array of columns to select from. If empty
     *                            all columns will be selected.
     * @param array    $where     An array of columns/values to filter the select 
     *                            query.
     * @return array  An array containing the SELECT query and bound parameters.
     */
    public static function buildSelectQuery($tableName, $dbType, array $columns = [], array $where = []) {
        if (!empty($columns)) {
            foreach ($columns as $i => $col) {
                $columns[$i] = self::quoteName($dbType, $col);
            }

            $insertCols = implode(', ', $columns);
        } else {
            $insertCols = '*';
        }

        $sql = "SELECT " . $insertCols . " FROM " . self::quoteName($dbType, $tableName);
        $where = self::buildWhereClause($dbType, $where);
        $sql .= $where["sql"];

        return array("sql" => $sql, "params" => $where["params"]);
    }

    /**
     * @param  string $tableName
     * @param  array  $where An array of columns/values to restrict the delete to.
     * @return array  An array containing the sql string and bound parameters.
     */
    public static function buildDeleteQuery($tableName, $dbType, array $where = []) {
        $sql = "DELETE FROM " . self::quoteName($dbType, $tableName);
        $where = self::buildWhereClause($dbType, $where);
        $sql .= $where["sql"];

        return array("sql" => $sql, "params" => $where["params"]);
    }

    /**
     * @param  string $tableName The name of the table to update.
     * @param  string $dbType    The database type ('mysql' or 'tsql')
     * @param  array  $set       An array of columns/values to update
     * @param  array  $where     An array of columns/values to restrict the update to.
     * @return array  An array containing the sql string and bound parameters.
     */
    public static function buildUpdateQuery($tableName, $dbType, array $set, array $where = []) {
        $sql = '';
        $params = [];

        if (!empty($set) && !empty($where)) {
            $sql = "UPDATE " . self::quoteName($dbType, $tableName) . " SET ";

            foreach ($set as $column => $value) {
                $sql .= self::quoteName($dbType, $column) . " = ?, ";
                $params[] = $value;
            }

            $sql = substr_replace($sql, "", -2); // remove trailing comma

            $where = self::buildWhereClause($dbType, $where);
            $sql .= $where["sql"];
            $params = array_merge($params, $where["params"]);
        }

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * @param string $dbType     The database type ('tsql' or 'mysql')
     * @param array  $columnVals An associative array of columns and values to
     *                           filter selected rows. E.g. ["id" => 3] to only
     *                           return rows where id is equal to 3.
     * @return array An array containing the SQL WHERE clause and bound parameters.
     */
    private static function buildWhereClause($dbType, array $columnVals) {
        $sql = "";
        $params = [];

        if (!empty($columnVals)) {
            $sql .= " WHERE";

            foreach ($columnVals as $column => $value) {
                if ($value === NULL) {
                    $comparison = "IS NULL";
                } else {
                    $comparison = "= ?";
                    $params[] = $value;
                }

                $sql .= " " . self::quoteName($dbType, $column) . " $comparison AND";
            }

            // remove the trailing AND
            $sql = substr_replace($sql, "", -4);
        }

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * @param  string $tableName
     * @param  array  $columns An array of columns to insert into.
     * @param  array  $values  A multi-dimensional array of values to insert into
     *                         the columns.
     * @return array  An array containing the SQL string and bound parameters.
     */
    public static function buildInsertQuery($tableName, $dbType, array $columns, array $values, $insertIdCol = NULL) {
        $sql = '';
        $params = [];

        if ($insertIdCol && $dbType === self::DBTYPE_TSQL) {
            $sql .= "DECLARE @ids TABLE(RowID int);";
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = self::quoteName($dbType, $col);
        }

        $insertCols = implode(', ', $columns);
        $sql .= "INSERT INTO " . self::quoteName($dbType, $tableName) . " ($insertCols)";

        if ($insertIdCol && $dbType === self::DBTYPE_TSQL) {
            $sql .= " OUTPUT inserted.[$insertIdCol] INTO @ids(RowID)";
        }

        $sql .= " VALUES";

        foreach ($values as $valArr) {
            $sql .= ' (';

            foreach ($valArr as $value) {
                $sql .= '?,';
                $params[] = $value;
            }

            $sql = substr_replace($sql, '', -1); // remove trailing comma

            $sql .= '),';
        }

        $sql = substr_replace($sql, '', -1); // remove trailing comma

        if ($insertIdCol && $dbType === self::DBTYPE_TSQL) {
            $sql .= ";SELECT * FROM @ids;";
        }

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * To bind parameters in mysqli, the type of each parameter must be specified.
     * See http://php.net/manual/en/mysqli-stmt.bind-param.php.
     * 
     * @param  array  $params
     * @return string A string containing the type character for each parameter
     */
    private static function getMysqlParamTypes(array $params) {
        // just treat all the parameters as strings since mysql "automatically 
        // converts strings to the column's actual datatype when processing 
        // queries" (see http://stackoverflow.com/a/14370546/1170489).

        return str_repeat("s", count($params));
    }

    /**
     * Quotes the table/column name based on the database type
     * @param string $name
     */
    private static function quoteName($dbType, $name) {
        if ($dbType === self::DBTYPE_TSQL) {
            return "[$name]";
        } else {
            return "`$name`";
        }
    }

    /**
     * Iterates through an array of rows, splitting it into groups when the
     * value of the specified column changes. Each group of rows is passed to the 
     * callback function. This can be used to separate complex data sets created
     * by joining tables in a single query. Note that the rows must be sorted by 
     * the column used to divide results.
     * 
     * @param array    $rows      An array of rows to split into groups
     * @param string   $divideCol The column used to split results into groups
     * @param callable $callback  function (array $itemSet)
     */
    public static function splitRows(array $rows, $divideCol, callable $callback) {
        if (!$divideCol) {
            throw new Exception("A divide column name must be specified");
        }

        $divideColVal = FALSE; // default
        $itemSet = [];

        foreach ($rows as $row) {

            if ($divideColVal === FALSE || $divideColVal !== $row[$divideCol]) {
                // new set of items

                if (!empty($itemSet)) {
                    // send previous set to callback
                    $callback($itemSet);
                }

                $itemSet = array($row); // start over
                $divideColVal = $row[$divideCol];
            } else {
                // same set of items
                $itemSet[] = $row; // append current row
            }
        }

        if (!empty($itemSet)) {
            // send last set to callback
            $callback($itemSet);
        }
    }

}
