<?php

namespace PeachySQL;

use PeachySQL\QueryBuilder\Delete;
use PeachySQL\QueryBuilder\Insert;
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
     * Takes a binary string and returns a value that can be bound to an insert/update statement
     * @param string $binaryStr
     * @param int | null $length
     * @return mixed
     */
    abstract public function makeBinaryParam($binaryStr, $length = null);

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
     * @param string $table
     * @param array $colVals E.g. [["Username => "user1", "Password" => "pass1"], ...]
     * @param int $identityIncrement
     * @return BulkInsertResult
     */
    abstract protected function insertBatch($table, array $colVals, $identityIncrement = 1);

    /**
     * Returns the current PeachySQL options
     * @return BaseOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $query
     * @return QueryableSelector
     */
    public function selectFrom($query)
    {
        return new QueryableSelector($query, $this);
    }

    /**
     * Inserts one row
     * @param string $table
     * @param array $colVals
     * @return InsertResult
     */
    public function insertRow($table, array $colVals)
    {
        $result = $this->insertBatch($table, [$colVals]);
        $ids = $result->getIds();
        $id = empty($ids) ? 0 : $ids[0];
        return new InsertResult($id, $result->getAffected());
    }

    /**
     * Insert multiple rows
     * @param string $table
     * @param array $colVals
     * @param int $identityIncrement
     * @return BulkInsertResult
     */
    public function insertRows($table, array $colVals, $identityIncrement = 1)
    {
        // check whether the query needs to be split into multiple batches
        $batches = Insert::batchRows($colVals, $this->options->getMaxBoundParams(), $this->options->getMaxInsertRows());
        $ids = [];
        $affected = 0;

        foreach ($batches as $batch) {
            $result = $this->insertBatch($table, $batch, $identityIncrement);
            $ids = array_merge($ids, $result->getIds());
            $affected += $result->getAffected();
        }

        return new BulkInsertResult($ids, $affected, count($batches));
    }

    /**
     * Updates the specified columns and values in rows matching the where clause
     * @param string $table
     * @param array $set
     * @param array $where
     * @return int the number of affected rows
     */
    public function updateRows($table, array $set, array $where)
    {
        $update = new Update($this->options);
        $sqlParams = $update->buildQuery($table, $set, $where);
        return $this->query($sqlParams->getSql(), $sqlParams->getParams())->getAffected();
    }

    /**
     * Deletes rows from the table matching the where clause
     * @param string $table
     * @param array $where
     * @return int the number of affected rows
     */
    public function deleteFrom($table, array $where)
    {
        $delete = new Delete($this->options);
        $sqlParams = $delete->buildQuery($table, $where);
        return $this->query($sqlParams->getSql(), $sqlParams->getParams())->getAffected();
    }

    /**
     * Selects the specified columns following the given where clause array
     *
     * @param string[] $columns An array of columns to select (empty to select all columns)
     * @param array    $where   An associative array of columns and values to filter selected rows.
     *                          E.g. ["id" => 3] to only return rows where the id column is equal to 3.
     * @param string[] $orderBy A list of column names to sort by (ascending only)
     * @return array
     * @deprecated Use selectFrom instead
     */
    public function select(array $columns = [], array $where = [], array $orderBy = [])
    {
        if (count($columns) !== 0) {
            $selectCols = implode(', ', array_map([$this->options, 'escapeIdentifier'], $columns));
        } else {
            $selectCols = '*';
        }

        return $this->selectFrom("SELECT {$selectCols} FROM " . $this->options->getTable())
            ->where($where)
            ->orderBy($orderBy)
            ->query()->getAll();
    }

    /**
     * Inserts one or more rows into the table
     *
     * @param array $colVals E.g. [["Username => "user1", "Password" => "pass1"], ...]
     * @return BulkInsertResult
     * @deprecated Use insertRows instead
     */
    public function insertBulk(array $colVals)
    {
        if (method_exists($this->options, 'getAutoIncrementValue')) {
            $identityIncrement = $this->options->getAutoIncrementValue();
        } else {
            $identityIncrement = 0;
        }

        return $this->insertRows($this->options->getTable(), $colVals, $identityIncrement);
    }

    /**
     * Inserts a single row from an associative array of columns/values.
     *
     * @param array $colVals E.g. ["Username => "user1", "Password" => "pass1"]
     * @return InsertResult
     * @deprecated Use insertRow instead
     */
    public function insertOne(array $colVals)
    {
        return $this->insertRow($this->options->getTable(), $colVals);
    }

    /**
     * Updates the specified columns and values in rows matching the where clause
     * 
     * @param array $set   E.g. ["Username" => "newName", "Password" => "newPass"]
     * @param array $where E.g. ["id" => 3] to update the row where id is equal to 3
     * @return int The number of affected rows
     * @deprecated Use updateRows instead
     */
    public function update(array $set, array $where)
    {
        return $this->updateRows($this->options->getTable(), $set, $where);
    }

    /**
     * Deletes rows from the table matching the where clause
     *
     * @param array $where E.g. ["id" => 3]
     * @return int The number of affected rows
     * @deprecated Use deleteFrom instead
     */
    public function delete(array $where)
    {
        return $this->deleteFrom($this->options->getTable(), $where);
    }
}
