<?php

namespace PeachySQL;

use PeachySQL\QueryBuilder\Insert;
use PeachySQL\SqlServer\Options;
use PeachySQL\SqlServer\Statement;

/**
 * Implements the standard PeachySQL features for SQL Server (using SQLSRV extension)
 * 
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class SqlServer extends PeachySql
{
    /**
     * A SQLSRV connection resource
     * @var resource
     */
    private $connection;

    public function __construct($connection, Options $options = null)
    {
        if (!is_resource($connection) || get_resource_type($connection) !== 'SQL Server Connection') {
            throw new \InvalidArgumentException('Connection must be a SQL Server Connection resource');
        }

        $this->connection = $connection;

        if ($options === null) {
            $options = new Options();
        }

        $this->options = $options;
    }

    /**
     * Begins a SQLSRV transaction
     * @throws SqlException if an error occurs
     */
    public function begin()
    {
        if (!sqlsrv_begin_transaction($this->connection)) {
            throw new SqlException('Failed to begin transaction', sqlsrv_errors());
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit()
    {
        if (!sqlsrv_commit($this->connection)) {
            throw new SqlException('Failed to commit transaction', sqlsrv_errors());
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback()
    {
        if (!sqlsrv_rollback($this->connection)) {
            throw new SqlException('Failed to roll back transaction', sqlsrv_errors());
        }
    }

    /**
     * Returns a prepared statement which can be executed multiple times
     * @param string $sql
     * @param array  $params Values to bind to placeholders in the query string
     * @return Statement
     * @throws SqlException if an error occurs
     */
    public function prepare($sql, array $params = [])
    {
        if (!$stmt = sqlsrv_prepare($this->connection, $sql, $params)) {
            throw new SqlException('Query failed', sqlsrv_errors(), $sql, $params);
        }

        return new Statement($stmt, true, $sql, $params);
    }

    /**
     * Prepares and executes a single SQL Server query
     * @param string $sql
     * @param array  $params Values to bind to placeholders in the query string
     * @return Statement
     * @throws SqlException if an error occurs
     */
    public function query($sql, array $params = [])
    {
        if (!$stmt = sqlsrv_query($this->connection, $sql, $params)) {
            throw new SqlException('Query failed', sqlsrv_errors(), $sql, $params);
        }

        $statement = new Statement($stmt, false, $sql, $params);
        $statement->execute();
        return $statement;
    }

    /**
     * Performs a single bulk insert query
     * @param array $colVals
     * @return BulkInsertResult
     */
    protected function insertBatch(array $colVals)
    {
        $sqlParams = Insert::buildQuery($this->options->getTable(), $colVals, $this->options->getColumns(), $this->options->getIdColumn());
        $result = $this->query($sqlParams->getSql(), $sqlParams->getParams());

        $ids = [];
        foreach ($result->getIterator() as $row) {
            $ids[] = $row['RowID'];
        }

        return new BulkInsertResult($ids, $result->getAffected());
    }
}
