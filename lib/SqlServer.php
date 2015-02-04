<?php

namespace PeachySQL;

use InvalidArgumentException;
use PeachySQL\QueryBuilder\Insert;

/**
 * Implements the standard PeachySQL features for SQL Server (using SQLSRV extension)
 * 
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class SqlServer extends PeachySql
{
    /**
     * Option key for specifying the table's ID column (used to retrieve insert IDs)
     */
    const OPT_IDCOL = 'idCol';

    /**
     * A SQLSRV connection resource
     * @var resource
     */
    private $connection;

    /**
     * Default SQL Server-specific options
     * @var array
     */
    private $sqlServerOptions = [
        self::OPT_IDCOL           => null,
        self::OPT_MAX_PARAMS      => 2099,
        self::OPT_MAX_INSERT_ROWS => 1000,
    ];

    /**
     * @param resource $connection A SQLSRV connection resource
     * @param array    $options    Array of PeachySQL options
     */
    public function __construct($connection, array $options = [])
    {
        $this->setConnection($connection);
        $this->setOptions($options);
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
     * Set options used to select, insert, update, and delete from the database
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->sqlServerOptions, $this->options);
        parent::setOptions($options);
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
        $query = Insert::buildQuery($this->options[self::OPT_TABLE], $colVals, $this->options[self::OPT_COLUMNS], $this->options[self::OPT_IDCOL]);
        $result = $this->query($query['sql'], $query['params']);

        $ids = [];
        foreach ($result->getIterator() as $row) {
            $ids[] = $row['RowID'];
        }

        return new BulkInsertResult($ids, $result->getAffected());
    }
}
