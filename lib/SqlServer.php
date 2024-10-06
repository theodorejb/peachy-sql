<?php

declare(strict_types=1);

namespace PeachySQL;

use PeachySQL\QueryBuilder\Insert;
use PeachySQL\SqlServer\Options;
use PeachySQL\SqlServer\Statement;

/**
 * Implements the standard PeachySQL features for SQL Server (using SQLSRV extension)
 */
class SqlServer extends PeachySql
{
    /**
     * A SQLSRV connection resource
     * @var resource
     */
    private $connection;

    /**
     * @param resource $connection
     */
    public function __construct($connection, ?Options $options = null)
    {
        if (get_resource_type($connection) !== 'SQL Server Connection') {
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
    public function begin(): void
    {
        if (!sqlsrv_begin_transaction($this->connection)) {
            throw new SqlException('Failed to begin transaction', sqlsrv_errors() ?? []);
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit(): void
    {
        if (!sqlsrv_commit($this->connection)) {
            throw new SqlException('Failed to commit transaction', sqlsrv_errors() ?? []);
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback(): void
    {
        if (!sqlsrv_rollback($this->connection)) {
            throw new SqlException('Failed to roll back transaction', sqlsrv_errors() ?? []);
        }
    }

    public function makeBinaryParam(?string $binaryStr, ?int $length = null)
    {
        if ($length === null) {
            $length = ($binaryStr === null) ? 1 : strlen($binaryStr);
        }

        return [
            $binaryStr,
            SQLSRV_PARAM_IN,
            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),
            SQLSRV_SQLTYPE_BINARY((string)$length),
        ];
    }

    /**
     * Returns a prepared statement which can be executed multiple times
     * @throws SqlException if an error occurs
     */
    public function prepare(string $sql, array $params = []): Statement
    {
        if (!$stmt = sqlsrv_prepare($this->connection, $sql, $params)) {
            throw new SqlException('Query failed', sqlsrv_errors() ?? [], $sql, $params);
        }

        return new Statement($stmt, true, $sql, $params);
    }

    /**
     * Prepares and executes a single SQL Server query with bound parameters
     * @throws SqlException if an error occurs
     */
    public function query(string $sql, array $params = []): Statement
    {
        if (!$stmt = sqlsrv_query($this->connection, $sql, $params)) {
            throw new SqlException('Query failed', sqlsrv_errors() ?? [], $sql, $params);
        }

        $statement = new Statement($stmt, false, $sql, $params);
        $statement->execute();
        return $statement;
    }

    /**
     * Performs a single bulk insert query
     */
    protected function insertBatch(string $table, array $colVals, int $identityIncrement = 1): BulkInsertResult
    {
        $sqlParams = (new Insert($this->options))->buildQuery($table, $colVals);
        $result = $this->query($sqlParams->sql, $sqlParams->params);
        $row = $result->getFirst();

        if (isset($row['RowID'])) {
            /** @var int $lastId */
            $lastId = $row['RowID'];
            $firstId = $lastId - $identityIncrement * (count($colVals) -1);
            $ids = range($firstId, $lastId, $identityIncrement);
        } else {
            $ids = [];
        }

        return new BulkInsertResult($ids, $result->getAffected());
    }
}
