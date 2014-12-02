<?php

namespace PeachySQL;

use PeachySQL\QueryBuilder\Delete;
use PeachySQL\QueryBuilder\Insert;
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
     * Option key for specifying the maximum number of parameters which can be bound in
     * a single query. If set, PeachySQL will batch insert queries to avoid the limit.
     */
    const OPT_MAX_PARAMS = "maxBoundParams";

    /**
     * Option key for specifying the maximum number of rows which can be inserted in
     * a single query. If set, PeachySQL will batch insert queries to remove the limit.
     */
    const OPT_MAX_INSERT_ROWS = "maxInsertRows";

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
     * Executes a single SQL query
     *
     * @param string $sql
     * @param array  $params
     * @return SqlResult
     */
    abstract public function query($sql, array $params = []);

    /**
     * @param array $colVals E.g. [["Username => "user1", "Password" => "pass1"], ...]
     * @return BulkInsertResult
     */
    abstract protected function insertBatch(array $colVals);

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
     * Selects the specified columns following the given where clause array
     *
     * @param string[] $columns An array of columns to select (empty to select all columns)
     * @param array    $where   An associative array of columns and values to filter selected rows.
     *                          E.g. ["id" => 3] to only return rows where the id column is equal to 3.
     * @return array
     */
    public function select(array $columns = [], array $where = [])
    {
        $query = Select::buildQuery($this->options[self::OPT_TABLE], $columns, $this->options[self::OPT_COLUMNS], $where);
        return $this->query($query['sql'], $query['params'])->getAll();
    }

    /**
     * Inserts one or more rows into the table
     *
     * @param array $colVals E.g. [["Username => "user1", "Password" => "pass1"], ...]
     * @return BulkInsertResult
     */
    public function insertBulk(array $colVals)
    {
        // check whether the query needs to be split into multiple batches
        $batches = Insert::batchRows($colVals, $this->options[self::OPT_MAX_PARAMS], $this->options[self::OPT_MAX_INSERT_ROWS]);

        if ($batches === null) {
            return $this->insertBatch($colVals);
        } else {
            $ids = [];
            $affected = 0;

            foreach ($batches as $batch) {
                $result = $this->insertBatch($batch);
                $ids = array_merge($ids, $result->getIds());
                $affected += $result->getAffected();
            }

            return new BulkInsertResult($ids, $affected, count($batches));
        }
    }

    /**
     * Inserts a single row from an associative array of columns/values.
     *
     * @param array $colVals E.g. ["Username => "user1", "Password" => "pass1"]
     * @return InsertResult
     */
    public function insertOne(array $colVals)
    {
        $result = $this->insertBatch([$colVals]);
        $ids = $result->getIds();
        $id = empty($ids) ? null : $ids[0];
        return new InsertResult($id, $result->getAffected());
    }

    /**
     * Inserts the specified values into the specified columns. Performs a bulk insert if
     * $values is two-dimensional. Returns the ID or array of IDs for the inserted row(s).
     *
     * @param array $columns
     * @param array $values
     * @return int[]|int
     * @deprecated since v3.0.0 - use insertBulk or insertOne instead
     */
    public function insert(array $columns, array $values)
    {
        if (Insert::isBulk($values)) {
            $colVals = [];

            foreach ($values as $row) {
                $colVals[] = array_combine($columns, $row);
            }

            return $this->insertBulk($colVals)->getIds();
        } else {
            $colVals = array_combine($columns, $values);
            return $this->insertOne($colVals)->getId();
        }
    }

    /**
     * Inserts a single row from an associative array of columns/values
     *
     * @param array $colVals E.g. ["Username => "user1", "Password" => "pass1"]
     * @return int The ID of the inserted row
     * @deprecated since v3.0.0 - use insertOne instead
     */
    public function insertAssoc(array $colVals)
    {
        return $this->insertOne($colVals)->getId();
    }

    /**
     * Updates the specified columns and values in rows matching the where clause
     * 
     * @param array $set   E.g. ["Username" => "newName", "Password" => "newPass"]
     * @param array $where E.g. ["id" => 3] to update the row where id is equal to 3
     * @return int The number of affected rows
     */
    public function update(array $set, array $where)
    {
        $query = Update::buildQuery($this->options[self::OPT_TABLE], $set, $where, $this->options[self::OPT_COLUMNS]);
        return $this->query($query['sql'], $query['params'])->getAffected();
    }

    /**
     * Deletes columns from the table where the where clause matches
     *
     * @param array $where E.g. ["id" => 3]
     * @return int The number of affected rows
     */
    public function delete(array $where)
    {
        $query = Delete::buildQuery($this->options[self::OPT_TABLE], $where, $this->options[self::OPT_COLUMNS]);
        return $this->query($query["sql"], $query["params"])->getAffected();
    }
}
