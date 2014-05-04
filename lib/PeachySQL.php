<?php

/**
 * Provides simple methods for executing queries with bound parameters and 
 * inserting, selecting, updating, and deleting rows in a table. Supports both
 * MySQL (via mysqli) and T-SQL (via Microsoft's SQLSRV extension) and can be 
 * easily extended to customize behavior.
 *
 * @author Theodore Brown <https://github.com/theodorejb>
 * @version 1.1.1  2014-04-20
 */
class PeachySQL {

    const DBTYPE_TSQL = 'tsql';
    const DBTYPE_MYSQL = 'mysql';

    /**
     * A mysqli object or sqlsrv connection resource
     * @var mixed
     */
    private $connection;

    /**
     * 'mysql' or 'tsql'
     * @var string
     */
    private $dbType;

    /**
     * Default options
     * @var array
     */
    private $options = [
        "table" => NULL, // required to use shorthand methods
        "idCol" => NULL  // used to retrieve insert IDs when using tsql
    ];

    /**
     * @param mixed $connection A SQLSRV connection resource or mysqli object
     * @param array $options (optional) Options used when querying the database
     */
    public function __construct($connection, array $options = []) {
        $this->setConnection($connection);
        $this->setOptions($options);
    }

    /**
     * Sets the connection which will be used to execute queries.
     * @param mixed $connection (mysqli instance or sqlsrv connection resource)
     * @throws Exception If the connection type is invalid
     */
    public function setConnection($connection) {
        if ($connection instanceof mysqli) {
            $this->dbType = self::DBTYPE_MYSQL;
        } elseif (is_resource($connection) && get_resource_type($connection) === 'SQL Server Connection') {
            $this->dbType = self::DBTYPE_TSQL;
        } else {
            // show a helpful error message
            $connType = gettype($connection);

            if ($connType === 'object') {
                $connType = get_class($connection) . " $connType";
            } elseif ($connType === 'resource') {
                $connType = get_resource_type($connection) . " $connType";
            }

            throw new Exception("Expected a mysqli object or SQL Server"
            . " Connection resource, but given a(n) $connType");
        }

        $this->connection = $connection;
    }

    /**
     * Returns the current connection type
     * @return string
     */
    public function getConnectionType() {
        return $this->dbType;
    }

    /**
     * Returns the current PeachySQL options.
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Allows PeachySQL options to be changed at any time
     * @param array $options
     * @throws Exception if an option is invalid
     */
    public function setOptions(array $options) {
        $validKeys = array_keys($this->options);

        foreach (array_keys($options) as $key) {
            if (!in_array($key, $validKeys, TRUE)) {
                throw new Exception("Invalid option '$key'");
            }
        }

        $this->options = array_merge($this->options, $options);
    }

    /**
     * Executes a single query and passes any errors, selected rows, and the 
     * number of affected rows to the callback function.
     * Returns the return value of the callback function.
     * 
     * MySQL only: If an INSERT query is performed on a table with an auto-
     *             incremented column, the $rows argument passed to the callback
     *             will contain the ID of the first inserted row.
     * 
     * @param string   $sql
     * @param array    $params Values to bind to placeholders in the query
     * @param callable $callback function (array $error, array $rows, int $affected)
     */
    public function query($sql, array $params, callable $callback) {
        switch ($this->dbType) {
            case self::DBTYPE_MYSQL:
                return $this->mysqlQuery($sql, $params, $callback);
            case self::DBTYPE_TSQL:
                return $this->tsqlQuery($sql, $params, $callback);
        }
    }

    /**
     * Executes a query and passes any errors, selected rows, and the number of 
     * affected rows to the callback function. Returns the callback's return value.
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

        return $callback($error, $rows, $affected);
    }

    /**
     * Executes a single query and passes any errors, selected rows (or insert 
     * id if performing an insert), and the number of affected rows to the 
     * callback function. Returns the return value of the callback.
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

                // rather silly hack to make call_user_func_array pass by reference
                foreach ($params as &$param) {
                    $typesValues[] = &$param;
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
                    // An insert statement was executed on a table with an 
                    // auto-incremented column. Pass the insert ID to the callback.
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

                                foreach ($data as $k => $v) {
                                    $rows[$i][$k] = $v;
                                }

                                $i++;
                            }
                        }
                    }
                }
            }

            $stmt->free_result();
            $stmt->close();
        }

        return $callback($error, $rows, $affected);
    }

    /**
     * Selects the specified columns following the given where clause array.
     * Returns the return value of the callback.
     * @param string[] $columns  An array of columns to select (empty to select
     *                           all columns).
     * @param array    $where    An associative array of columns and values to
     *                           filter selected rows. E.g. ["id" => 3] to only
     *                           return rows where the id column is equal to 3.
     * @param callable $callback function (array $error, array $rows)
     */
    public function select(array $columns, array $where, callable $callback) {
        $query = self::buildSelectQuery($this->options["table"], $columns, $where);
        return $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * Insert one or more rows into the table. Returns the return value of the
     * callback function.
     * 
     * @param string[] $columns  The columns to be inserted into. E.g.
     *                           ["Username", "Password].
     * @param array    $values   An array containing one or more subarrays with
     *                           values for each column. E.g.
     *                           [ ["user1", "pass1"], ["user2", "pass2"] ]
     * @param callable $callback function (array $error, array $insertIDs, int $affected)
     */
    public function insert(array $columns, array $values, callable $callback) {
        $query = self::buildInsertQuery($this->options["table"], $this->dbType, $columns, $values, $this->options["idCol"]);
        $sql = $query["sql"];
        $params = $query["params"];

        return $this->query($sql, $params, function ($err, $rows, $affected) use ($values, $callback) {
            $ids = [];

            switch ($this->dbType) {
                case self::DBTYPE_MYSQL:
                    if (isset($rows[0])) {
                        // $rows contains the ID of the first inserted row
                        $firstInsertId = $rows[0];

                        if (is_array($values[0])) {
                            // bulk insert
                            $lastInsertId = $firstInsertId + (count($values) - 1);
                            $ids = range($firstInsertId, $lastInsertId);
                        } else {
                            // only a single row was inserted
                            $ids = [$firstInsertId];
                        }
                    }
                    break;
                case self::DBTYPE_TSQL:
                    // if non-empty, $rows contains an array of insert ID rows
                    foreach ($rows as $row) {
                        $ids[] = $row["RowID"];
                    }
                    break;
            }

            return $callback($err, $ids, $affected);
        });
    }

    /**
     * Updates the specified columns and values in rows matching the where clause.
     * Returns the return value of the callback function.
     * 
     * @param array $set   E.g. ["Username" => "newUsername", "Password" => "newPassword"]
     * @param array $where E.g. ["id" => 3] to update the row where id is equal to 3.
     * @param callable $callback function (array $error, int $affected)
     */
    public function update(array $set, array $where, callable $callback) {
        $query = self::buildUpdateQuery($this->options["table"], $set, $where);
        return $this->query($query["sql"], $query["params"], function ($err, $rows, $affected) use ($callback) {
            return $callback($err, $affected);
        });
    }

    /**
     * Deletes columns from the table where the where clause matches.
     * Returns the return value of the callback function.
     * 
     * @param array    $where    E.g. ["id" => 3]
     * @param callable $callback function (array $error, int $affected)
     */
    public function delete(array $where, callable $callback) {
        $query = self::buildDeleteQuery($this->options["table"], $where);
        return $this->query($query["sql"], $query["params"], function ($err, $rows, $affected) use ($callback) {
            return $callback($err, $affected);
        });
    }

    /**
     * If the array is empty, the query should select all rows. If not empty,
     * filter by the column values
     * 
     * @param string   $tableName The name of the table to query
     * @param string[] $columns   An array of columns to select from. If empty
     *                            all columns will be selected.
     * @param array    $where     An array of columns/values to filter the select 
     *                            query.
     * @return array  An array containing the SELECT query and bound parameters.
     */
    public static function buildSelectQuery($tableName, array $columns = [], array $where = []) {
        self::validateTableName($tableName, "a select");

        if (!empty($columns)) {
            $insertCols = implode(', ', $columns);
        } else {
            $insertCols = '*';
        }

        $sql = "SELECT $insertCols FROM $tableName";
        $where = self::buildWhereClause($where);
        $sql .= $where["sql"];

        return array("sql" => $sql, "params" => $where["params"]);
    }

    /**
     * @param  string $tableName
     * @param  array  $where An array of columns/values to restrict the delete to.
     * @return array  An array containing the sql string and bound parameters.
     */
    public static function buildDeleteQuery($tableName, array $where = []) {
        self::validateTableName($tableName, "a delete");

        $sql = "DELETE FROM $tableName";
        $where = self::buildWhereClause($where);
        $sql .= $where["sql"];

        return array("sql" => $sql, "params" => $where["params"]);
    }

    /**
     * @param  string $tableName The name of the table to update.
     * @param  array  $set       An array of columns/values to update
     * @param  array  $where     An array of columns/values to restrict the update to.
     * @return array  An array containing the sql string and bound parameters.
     */
    public static function buildUpdateQuery($tableName, array $set, array $where = []) {
        self::validateTableName($tableName, "an update");
        $sql = '';
        $params = [];

        if (!empty($set) && !empty($where)) {
            $sql = "UPDATE $tableName SET ";

            foreach ($set as $column => $value) {
                $sql .= "$column = ?, ";
                $params[] = $value;
            }

            $sql = substr_replace($sql, "", -2); // remove trailing comma

            $where = self::buildWhereClause($where);
            $sql .= $where["sql"];
            $params = array_merge($params, $where["params"]);
        }

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * @param array  $columnVals An associative array of columns and values to
     *                           filter selected rows. E.g. ["id" => 3] to only
     *                           return rows where id is equal to 3. If the value
     *                           is an array, an IN(...) clause will be used.
     * @return array An array containing the SQL WHERE clause and bound parameters.
     */
    private static function buildWhereClause(array $columnVals) {
        $sql = "";
        $params = [];

        if (!empty($columnVals)) {
            $sql .= " WHERE";

            foreach ($columnVals as $column => $value) {
                if ($value === NULL) {
                    $comparison = "IS NULL";
                } elseif (is_array($value) && !empty($value)) {
                    // use IN(...) syntax
                    $comparison = "IN(";

                    foreach ($value as $val) {
                        $comparison .= '?,';
                        $params[] = $val;
                    }

                    $comparison = substr_replace($comparison, ")", -1); // replace trailing comma
                } else {
                    $comparison = "= ?";
                    $params[] = $value;
                }

                $sql .= " $column $comparison AND";
            }

            $sql = substr_replace($sql, "", -4); // remove the trailing AND
        }

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * @param string $tableName The name of the table to insert into
     * @param string $dbType    The database type ('mysql' or 'tsql')
     * @param array  $columns   An array of columns to insert into.
     * @param array  $values    A multi-dimensional array of values to insert 
     *                          into the columns.
     * @param string $idCol     The name of the table's primary key column. Must
     *                          be specified when using T-SQL to get insert IDs.
     * @return array An array containing the SQL string and bound parameters.
     */
    public static function buildInsertQuery($tableName, $dbType, array $columns, array $values, $idCol = NULL) {
        self::validateTableName($tableName, "an insert");
        $sql = '';
        $params = [];

        if ($idCol && $dbType === self::DBTYPE_TSQL) {
            $sql .= "DECLARE @ids TABLE(RowID int);";
        }

        $insertCols = implode(', ', $columns);
        $sql .= "INSERT INTO $tableName ($insertCols)";

        if ($idCol && $dbType === self::DBTYPE_TSQL) {
            $sql .= " OUTPUT inserted.$idCol INTO @ids(RowID)";
        }

        $sql .= " VALUES";

        if (is_array($values[0])) {
            // inserting multiple rows
            foreach ($values as $valArr) {
                $sql .= ' ' . self::generateBoundParamsList($valArr) . ',';
                $params = array_merge($params, $valArr);
            }

            $sql = substr_replace($sql, '', -1); // remove trailing comma
        } else {
            // inserting single row
            $sql .= ' ' . self::generateBoundParamsList($values);
            $params = $values;
        }

        if ($idCol && $dbType === self::DBTYPE_TSQL) {
            $sql .= ";SELECT * FROM @ids;";
        }

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * Returns a parenthesized list of placeholders for the values
     * @param array $values
     * @return string
     */
    public static function generateBoundParamsList(array $values) {
        $sql = '(';
        $sql .= str_repeat('?,', count($values));
        $sql = substr_replace($sql, ')', -1); // replace trailing comma
        return $sql;
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
     * Throws an exception if the table name is null or blank
     * @param string $name
     * @throws Exception
     */
    private static function validateTableName($name, $type = "this") {
        if ($name === NULL || $name === "") {
            throw new Exception("A valid table name must be set to generate $type query");
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
