<?php

declare(strict_types=1);

namespace PeachySQL;

use PeachySQL\QueryBuilder\Delete;
use PeachySQL\QueryBuilder\Insert;
use PeachySQL\QueryBuilder\SqlParams;
use PeachySQL\QueryBuilder\Update;

/**
 * Provides reusable functionality and can be extended by database-specific classes
 * @psalm-import-type WhereClause from QueryBuilder\Query
 * @psalm-import-type ColValues from Insert
 */
abstract class PeachySql
{
    protected BaseOptions $options;

    /** Begins a transaction */
    abstract public function begin(): void;

    /** Commits a transaction begun with begin() */
    abstract public function commit(): void;

    /** Rolls back a transaction begun with begin() */
    abstract public function rollback(): void;

    /**
     * Takes a binary string and returns a value that can be bound to an insert/update statement
     * @return string|null|array
     */
    abstract public function makeBinaryParam(?string $binaryStr, ?int $length = null);

    /**
     * Prepares a SQL query for later execution
     */
    abstract public function prepare(string $sql, array $params = []): BaseStatement;

    /**
     * Executes a single SQL query
     */
    abstract public function query(string $sql, array $params = []): BaseStatement;

    /**
     * @param list<ColValues> $colVals
     */
    abstract protected function insertBatch(string $table, array $colVals, int $identityIncrement = 1): BulkInsertResult;

    /**
     * Returns the current PeachySQL options
     */
    public function getOptions(): BaseOptions
    {
        return $this->options;
    }

    public function selectFrom(string $query): QueryableSelector
    {
        return new QueryableSelector(new SqlParams($query, []), $this);
    }

    public function select(SqlParams $query): QueryableSelector
    {
        return new QueryableSelector($query, $this);
    }

    /**
     * Inserts one row
     * @param ColValues $colVals
     */
    public function insertRow(string $table, array $colVals): InsertResult
    {
        $result = $this->insertBatch($table, [$colVals]);
        $ids = $result->ids;
        return new InsertResult($ids ? $ids[0] : 0, $result->affected);
    }

    /**
     * Insert multiple rows
     * @param list<ColValues> $colVals
     */
    public function insertRows(string $table, array $colVals, int $identityIncrement = 1): BulkInsertResult
    {
        // check whether the query needs to be split into multiple batches
        $batches = Insert::batchRows($colVals, $this->options->getMaxBoundParams(), $this->options->getMaxInsertRows());
        $ids = [];
        $affected = 0;

        foreach ($batches as $batch) {
            $result = $this->insertBatch($table, $batch, $identityIncrement);
            $ids = array_merge($ids, $result->ids);
            $affected += $result->affected;
        }

        return new BulkInsertResult($ids, $affected, count($batches));
    }

    /**
     * Updates the specified columns and values in rows matching the where clause
     * Returns the number of affected rows
     * @param ColValues $set
     * @param WhereClause $where
     */
    public function updateRows(string $table, array $set, array $where): int
    {
        $update = new Update($this->options);
        $sqlParams = $update->buildQuery($table, $set, $where);
        return $this->query($sqlParams->sql, $sqlParams->params)->getAffected();
    }

    /**
     * Deletes rows from the table matching the where clause
     * Returns the number of affected rows
     * @param WhereClause $where
     */
    public function deleteFrom(string $table, array $where): int
    {
        $delete = new Delete($this->options);
        $sqlParams = $delete->buildQuery($table, $where);
        return $this->query($sqlParams->sql, $sqlParams->params)->getAffected();
    }
}
