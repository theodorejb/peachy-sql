<?php

namespace PeachySQL;

use PeachySQL\QueryBuilder\Delete;
use PeachySQL\QueryBuilder\Select;
use PeachySQL\QueryBuilder\Update;

/**
 * Provides reusable functionality and can be extended by database-specific classes
 *
 * @author Theodore Brown <https://github.com/theodorejb>
 */
abstract class PeachySql
{
    /**
     * Option key for specifying the table to select, insert, update, and delete from
     */
    const OPT_TABLE = "table";

    /**
     * Option key for specifying valid columns in the table. To prevent SQL injection,
     * this option must be set to generate queries which reference one or more columns.
     */
    const OPT_COLUMNS = "columns";

    /**
     * Default options
     * @var array
     */
    protected $options = [
        self::OPT_TABLE => null,
        self::OPT_COLUMNS => [],
    ];

    /** Begins a transaction */
    abstract public function begin();

    /** Commits a transaction begun with begin() */
    abstract public function commit();

    /** Rolls back a transaction begun with begin() */
    abstract public function rollback();

    /**
     * Executes a single query and passes a SqlResult object to the callback
     * @param string   $sql
     * @param array    $params
     * @param callable $callback
     * @return SqlResult|mixed The return value of the callback
     * @throws SqlException if an error occurs
     */
    abstract public function query($sql, array $params = [], callable $callback = null);

    /**
     * Inserts the specified values into the specified columns. Performs a bulk 
     * insert if $values is two-dimensional.
     * @param array $columns
     * @param array $values
     * @param callable $callback function ($ids, SqlResult $result)
     */
    abstract public function insert(array $columns, array $values, callable $callback = null);

    /**
     * Returns the current PeachySQL options.
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Allows PeachySQL options to be changed at any time
     * @param array $options
     * @throws \Exception if an option is invalid
     */
    public function setOptions(array $options)
    {
        $validKeys = array_keys($this->options);

        foreach (array_keys($options) as $key) {
            if (!in_array($key, $validKeys, true)) {
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
     * @param callable $callback function (SqlResult $result)
     * @return array
     */
    public function select(array $columns = [], array $where = [], callable $callback = null)
    {
        if ($callback === null) {
            $callback = function (SqlResult $result) {
                return $result->getAll();
            };
        }

        $query = Select::buildQuery($this->options[self::OPT_TABLE], $columns, $this->options[self::OPT_COLUMNS], $where);
        return $this->query($query['sql'], $query['params'], $callback);
    }

    /**
     * Inserts a single row from an associative array of columns/values.
     * @param array    $colVals  E.g. ["Username => "user1", "Password" => "pass1"]
     * @param callable $callback function (int $insertId, SqlResult $result)
     * @return mixed The insert ID, or the return value of the callback
     */
    public function insertAssoc(array $colVals, callable $callback = null)
    {
        if ($callback === null) {
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
     * @param callable $callback function (SqlResult $result)
     * @return mixed The number of affected rows, or the return value of the callback
     */
    public function update(array $set, array $where, callable $callback = null)
    {
        if ($callback === null) {
            $callback = function (SqlResult $result) {
                return $result->getAffected();
            };
        }

        $query = Update::buildQuery($this->options[self::OPT_TABLE], $set, $where, $this->options[self::OPT_COLUMNS]);
        return $this->query($query['sql'], $query['params'], $callback);
    }

    /**
     * Deletes columns from the table where the where clause matches.
     * Returns the return value of the callback function.
     * 
     * @param array    $where    E.g. ["id" => 3]
     * @param callable $callback function (SqlResult $result)
     * @return mixed The number of affected rows, or the return value of the callback
     */
    public function delete(array $where, callable $callback = null)
    {
        if ($callback === null) {
            $callback = function (SqlResult $result) {
                return $result->getAffected();
            };
        }

        $query = Delete::buildQuery($this->options[self::OPT_TABLE], $where, $this->options[self::OPT_COLUMNS]);
        return $this->query($query["sql"], $query["params"], $callback);
    }
}
