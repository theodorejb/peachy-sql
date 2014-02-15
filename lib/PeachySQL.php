<?php

/**
 * Provides abstract methods for inserting, selecting, updating, and deleting
 * rows in a table. Can be extended by individual table classes.
 *
 * @author Theodore Brown <https://github.com/theodorejb>
 * @version 0.5
 */
class PeachySQL {

    /**
     * An SQLSRV database connection
     * @var SQLSRV
     */
    private $connection;

    /**
     * The name of the table to query
     * @var string
     */
    private $tableName;

    public function __construct($connection, $tableName = NULL) {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    /**
     * Executes multiple queries separated by semicolons, and passes any errors,
     * selected rows, and the number of affected rows to the callback function. 
     * Transactions are not supported.
     * 
     * @param string   $sql
     * @param array    $params
     * @param callable $callback function (array $errors, array $rows, int $affected)
     */
    public function query($sql, array $params, callable $callback) {
        $errors = NULL;
        $rows = [];
        $affected = 0;

        if (!$stmt = sqlsrv_query($this->connection, $sql, $params)) {
            $errors = sqlsrv_errors();
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
                $errors = sqlsrv_errors();
            }

            sqlsrv_free_stmt($stmt);
        }

        // errors are passed rather than thrown to support more flexible usage
        $callback($errors, $rows, $affected);
    }

    /**
     * @param array    $columnVals An associative array of columns and values to
     *                             filter selected rows. E.g. ["id" => 3] to only
     *                             return rows where id is equal to 3.
     * @param callable $callback   Same as query() callback
     */
    public function select(array $columnVals, callable $callback) {
        $query = self::buildSelectQuery($this->tableName, $columnVals);
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
     *                           column will be passed to the callback.
     * @param callable $callback function (array $errors, array $insertIDs)
     */
    public function insert(array $columns, array $values, $idCol, callable $callback) {
        $query = self::buildInsertQuery($this->tableName, $columns, $values, $idCol);
        $sql = $query["sql"];
        $params = $query["params"];

        $this->query($sql, $params, function ($err, $rows) use ($callback) {
            $ids = [];

            foreach ($rows as $row) {
                $ids[] = $row["RowID"];
            }

            $callback($err, $ids);
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
        $query = self::buildUpdateQuery($this->tableName, $set, $where);
        $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * Deletes columns from the table where the where clause matches.
     * 
     * @param array    $where    E.g. ["id" => 3]
     * @param callable $callback function (array $errors, array $rows, array $affected)
     */
    public function delete(array $where, callable $callback) {
        $query = self::buildDeleteQuery($this->tableName, $where);
        $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * If the array is empty, the query should select all rows. If not empty,
     * filter by the column values
     * 
     * @param string $tableName The name of the table to query
     * @param array  $where An array of columns/values to filter the select query.
     * 
     * @param  string $orderBy The column to order results by
     * @return array  An array containing the SELECT query and bound parameters.
     */
    public static function buildSelectQuery($tableName, array $where = []) {
        $sql = "SELECT * FROM [$tableName]";
        $where = self::buildWhereClause($where);

        $params = $where["params"];
        $sql .= $where["sql"];

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * @param  string $tableName
     * @param  array  $where An array of columns/values to restrict the delete to.
     * @return array  An array containing the sql string and bound parameters.
     */
    public static function buildDeleteQuery($tableName, array $where = []) {
        $sql = "DELETE FROM [$tableName]";
        $where = self::buildWhereClause($where);

        $params = $where["params"];
        $sql .= $where["sql"];

        return array("sql" => $sql, "params" => $params);
    }

    /**
     * @param  string $tableName
     * @param  array  $set   An array of columns/values to update
     * @param  array  $where An array of columns/values to restrict the update to.
     * @return array  An array containing the sql string and bound parameters.
     */
    public static function buildUpdateQuery($tableName, array $set, array $where = []) {
        $sql = '';
        $params = [];

        if (!empty($set) && !empty($where)) {
            $sql = "UPDATE [$tableName] SET ";

            foreach ($set as $column => $value) {
                $sql .= "[$column] = ?, ";
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
     * @param array $columnVals An associative array of columns and values to
     *                          filter selected rows. E.g. ["id" => 3] to only
     *                          return rows where id is equal to 3.
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
                } else {
                    $comparison = "= ?";
                    $params[] = $value;
                }

                $sql .= " [$column] $comparison AND";
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
    public static function buildInsertQuery($tableName, array $columns, array $values, $insertIdCol = NULL) {
        $sql = '';
        $params = [];

        if ($insertIdCol) {
            $sql .= "DECLARE @ids TABLE(RowID int);";
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = "[$col]";
        }

        $insertCols = implode(', ', $columns);
        $sql .= "INSERT INTO [$tableName] ($insertCols)";

        if ($insertIdCol) {
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

        $sql = substr_replace($sql, ';', -1); // replace trailing comma

        if ($insertIdCol) {
            $sql .= "SELECT * FROM @ids;";
        }

        return array("sql" => $sql, "params" => $params);
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
