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
     * @var BaseOptions
     */
    protected $options;

    /** Begins a transaction */
    abstract public function begin();

    /** Commits a transaction begun with begin() */
    abstract public function commit();

    /** Rolls back a transaction begun with begin() */
    abstract public function rollback();

    /**
     * Prepares a SQL query for later execution
     *
     * @param string $sql
     * @param array $params
     * @return BaseStatement
     */
    abstract public function prepare($sql, array $params = []);

    /**
     * Executes a single SQL query
     *
     * @param string $sql
     * @param array  $params
     * @return BaseStatement
     */
    abstract public function query($sql, array $params = []);

    /**
     * @param array $colVals E.g. [["Username => "user1", "Password" => "pass1"], ...]
     * @return BulkInsertResult
     */
    abstract protected function insertBatch(array $colVals);

    /**
     * Returns the current PeachySQL options
     * @return BaseOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Selects the specified columns following the given where clause array
     *
     * @param string[] $columns An array of columns to select (empty to select all columns)
     * @param array    $where   An associative array of columns and values to filter selected rows.
     *                          E.g. ["id" => 3] to only return rows where the id column is equal to 3.
     * @param string[] $orderBy A list of column names to sort by (ascending only)
     * @return array
     */
    public function select(array $columns = [], array $where = [], array $orderBy = [])
    {
        $query = Select::buildQuery($this->options->getTable(), $columns, $this->options->getColumns(), $where, $orderBy);
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
        $batches = Insert::batchRows($colVals, $this->options->getMaxBoundParams(), $this->options->getMaxInsertRows());
        $ids = [];
        $affected = 0;

        foreach ($batches as $batch) {
            $result = $this->insertBatch($batch);
            $ids = array_merge($ids, $result->getIds());
            $affected += $result->getAffected();
        }

        return new BulkInsertResult($ids, $affected, count($batches));
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
     * Updates the specified columns and values in rows matching the where clause
     * 
     * @param array $set   E.g. ["Username" => "newName", "Password" => "newPass"]
     * @param array $where E.g. ["id" => 3] to update the row where id is equal to 3
     * @return int The number of affected rows
     */
    public function update(array $set, array $where)
    {
        $query = Update::buildQuery($this->options->getTable(), $set, $where, $this->options->getColumns());
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
        $query = Delete::buildQuery($this->options->getTable(), $where, $this->options->getColumns());
        return $this->query($query['sql'], $query['params'])->getAffected();
    }
}
