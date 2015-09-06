<?php

namespace PeachySQL;

use InvalidArgumentException;
use PeachySQL\QueryBuilder\Insert;
use PeachySQL\SqlServer\Options;

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
        $this->setConnection($connection);

        if ($options === null) {
            $options = new Options();
        }

        $this->options = $options;
    }

    /**
     * Easily switch to a different SQL Server database connection
     * @param resource $connection
     * @throws InvalidArgumentException if the connection isn't an SQLSRV resource
     */
    public function setConnection($connection)
    {
        if (!is_resource($connection) || get_resource_type($connection) !== 'SQL Server Connection') {
            throw new InvalidArgumentException('Connection must be a SQL Server Connection resource');
        }

        $this->connection = $connection;
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
     * Executes a single SQL Server query
     *
     * @param string $sql
     * @param array  $params Values to bind to placeholders in the query string
     * @return SqlServerResult
     * @throws SqlException if an error occurs
     */
    public function query($sql, array $params = [])
    {
        if (!$stmt = sqlsrv_query($this->connection, $sql, $params)) {
            throw new SqlException('Query failed', sqlsrv_errors(), $sql, $params);
        }

        return new SqlServerResult($stmt, $sql, $params);
    }

    /**
     * Performs a single bulk insert query
     * @param array $colVals
     * @return BulkInsertResult
     */
    protected function insertBatch(array $colVals)
    {
        $query = Insert::buildQuery($this->options->getTable(), $colVals, $this->options->getColumns(), $this->options->getIdColumn());
        $result = $this->query($query['sql'], $query['params']);

        $ids = [];
        foreach ($result->getIterator() as $row) {
            $ids[] = $row['RowID'];
        }

        return new BulkInsertResult($ids, $result->getAffected());
    }
}
