<?php

namespace PeachySQL;

/**
 * Provides reusable functionality and can be extended by database-specific classes
 *
 * @author Theodore Brown <https://github.com/theodorejb>
 * @version 2.0.0-beta.5 2014-05-25
 */
abstract class PeachySQL {

    /**
     * Option key for specifying the table to select, insert, update, and delete from
     */
    const OPT_TABLE = "table";

    /**
     * Default options
     * @var array
     */
    protected $options = [
        self::OPT_TABLE => NULL,
    ];

    /** Begins a transaction */
    public abstract function begin();

    /** Commits a transaction begun with begin() */
    public abstract function commit();

    /** Rolls back a transaction begun with begin() */
    public abstract function rollback();

    /**
     * Executes a single query and passes a SQLResult object to the callback
     * @param string   $sql
     * @param array    $params
     * @param callable $callback
     * @return mixed The return value of the callback
     * @throws SQLException if an error occurs
     */
    public abstract function query($sql, array $params = [], callable $callback = NULL);

    /**
     * Inserts the specified values into the specified columns. Performs a bulk 
     * insert if $values is two-dimensional.
     * @param array $columns
     * @param array $values
     * @param callable $callback function ($ids, SQLResult $result)
     */
    public abstract function insert(array $columns, array $values, callable $callback = NULL);

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
                throw new \Exception("Invalid option '$key'");
            }
        }

        $this->options = array_merge($this->options, $options);
    }

    /**
     * Selects the specified columns following the given where clause array.
     * Returns the return value of the callback.
     * @param string[] $columns  An array of columns to select (empty to select
     *                           all columns).
     * @param array    $where    An associative array of columns and values to
     *                           filter selected rows. E.g. ["id" => 3] to only
     *                           return rows where the id column is equal to 3.
     * @param callable $callback function (SQLResult $result)
     */
    public function select(array $columns = [], array $where = [], callable $callback = NULL) {
        if ($callback === NULL) {
            $callback = function (SQLResult $result) {
                return $result->getRows();
            };
        }

        $query = self::buildSelectQuery($this->options[self::OPT_TABLE], $columns, $where);
        return $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * Inserts a single row from an associative array of columns/values.
     * @param array    $colVals  E.g. ["Username => "user1", "Password" => "pass1"]
     * @param callable $callback function (int $insertId, SQLResult $result)
     * @return mixed The insert ID, or the return value of the callback
     */
    public function insertAssoc(array $colVals, callable $callback = NULL) {
        if ($callback === NULL) {
            $callback = function ($id) {
                return $id;
            };
        }

        return $this->insert(array_keys($colVals), array_values($colVals), $callback);
    }

    /**
     * Updates the specified columns and values in rows matching the where clause.
     * 
     * @param array    $set   E.g. ["Username" => "newName", "Password" => "newPass"]
     * @param array    $where E.g. ["id" => 3] to update the row where id is equal to 3
     * @param callable $callback function (SQLResult $result)
     * @return mixed The number of affected rows, or the return value of the callback
     */
    public function update(array $set, array $where, callable $callback = NULL) {
        if ($callback === NULL) {
            $callback = function (SQLResult $result) {
                return $result->getAffected();
            };
        }

        $query = self::buildUpdateQuery($this->options[self::OPT_TABLE], $set, $where);
        return $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * Deletes columns from the table where the where clause matches.
     * Returns the return value of the callback function.
     * 
     * @param array    $where    E.g. ["id" => 3]
     * @param callable $callback function (SQLResult $result)
     * @return mixed The number of affected rows, or the return value of the callback
     */
    public function delete(array $where, callable $callback = NULL) {
        if ($callback === NULL) {
            $callback = function (SQLResult $result) {
                return $result->getAffected();
            };
        }

        $query = self::buildDeleteQuery($this->options[self::OPT_TABLE], $where);
        return $this->query($query["sql"], $query["params"], $callback);
    }

    /**
     * Builds a selct query using the specified table name, columns, and where clause array.
     * @param  string   $tableName The name of the table to query
     * @param  string[] $columns   An array of columns to select from (all columns if empty)
     * @param  array    $where     An array of columns/values to filter the select query
     * @return array    An array containing the SELECT query and bound parameters
     */
    public static function buildSelectQuery($tableName, array $columns = [], array $where = []) {
        self::validateTableName($tableName, "a select");
        $where = self::buildWhereClause($where);

        if (!empty($columns)) {
            $insertCols = implode(', ', $columns);
        } else {
            $insertCols = '*';
        }

        $sql = "SELECT $insertCols FROM $tableName" . $where["sql"];
        return ["sql" => $sql, "params" => $where["params"]];
    }

    /**
     * @param  string $tableName
     * @param  array  $where An array of columns/values to restrict the delete to.
     * @return array  An array containing the sql string and bound parameters.
     */
    public static function buildDeleteQuery($tableName, array $where = []) {
        self::validateTableName($tableName, "a delete");
        $where = self::buildWhereClause($where);
        $sql = "DELETE FROM $tableName" . $where["sql"];
        return ["sql" => $sql, "params" => $where["params"]];
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
     * Returns an associative array containing bound params and separate INSERT
     * and VALUES strings. Allows reusability across database implementations.
     * 
     * @param string   $tableName The name of the table to insert into
     * @param string[] $columns   An array of columns to insert into.
     * @param array    $values    A two-dimensional array of values to insert into the columns.
     * @return array
     */
    protected static function buildInsertQueryComponents($tableName, array $columns, array $values) {
        self::validateTableName($tableName, "an insert");

        $bulkInsert = isset($values[0]) && is_array($values[0]);
        if (!$bulkInsert) {
            $values = [$values]; // make sure values is two-dimensional
        }

        // make sure columns and values are specified
        if (empty($columns) || empty($values[0])) {
            throw new \Exception("Columns and values to insert must be specified");
        }

        $insertCols = implode(', ', $columns);
        $insert = "INSERT INTO $tableName ($insertCols)";

        $params = [];
        $valStr = ' VALUES';

        foreach ($values as $valArr) {
            $valStr .= ' (' . str_repeat('?,', count($valArr));
            $valStr = substr_replace($valStr, '),', -1); // replace trailing comma
            $params = array_merge($params, $valArr);
        }

        $valStr = substr_replace($valStr, '', -1); // remove trailing comma

        return [
            'insertStr' => $insert,
            'valStr'    => $valStr,
            'params'    => $params,
            'isBulk'    => $bulkInsert
        ];
    }

    /**
     * Throws an exception if the table name is null or blank
     * @param string $name
     * @throws Exception
     */
    private static function validateTableName($name, $type = "this") {
        if ($name === NULL || $name === "") {
            throw new \Exception("A valid table name must be set to generate $type query");
        }
    }

}
