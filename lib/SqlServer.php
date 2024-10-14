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
    private $conn;

    /**
     * @param resource $connection
     */
    public function __construct($connection, ?Options $options = null)
    {
        if (get_resource_type($connection) !== 'SQL Server Connection') {
            throw new \InvalidArgumentException('Connection must be a SQL Server Connection resource');
        }

        $this->conn = $connection;

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
        if (!sqlsrv_begin_transaction($this->conn)) {
            throw $this->getError('Failed to begin transaction');
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit(): void
    {
        if (!sqlsrv_commit($this->conn)) {
            throw $this->getError('Failed to commit transaction');
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback(): void
    {
        if (!sqlsrv_rollback($this->conn)) {
            throw $this->getError('Failed to roll back transaction');
        }
    }

    public function makeBinaryParam(?string $binaryStr, ?int $length = null): array
    {
        $param = [
            $binaryStr,
            SQLSRV_PARAM_IN,
            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),
        ];

        if ($length !== null) {
            /** @psalm-suppress MixedAssignment */
            $param[] = SQLSRV_SQLTYPE_BINARY((string) $length);
        }

        return $param;
    }

    /**
     * @internal
     */
    public static function getError(string $message): SqlException
    {
        $errors = sqlsrv_errors() ?? [];
        $code = 0;
        $details = '';
        $sqlState = '';

        if (isset($errors[0])) {
            /** @var array{SQLSTATE: string, code: int, message: string} $error */
            $error = $errors[0];
            $code = $error['code'];
            $details = $error['message'];
            $sqlState = $error['SQLSTATE'];
        }

        return new SqlException($message, $code, $details, $sqlState);
    }

    /**
     * Returns a prepared statement which can be executed multiple times
     * @throws SqlException if an error occurs
     */
    public function prepare(string $sql, array $params = []): Statement
    {
        if (!$stmt = sqlsrv_prepare($this->conn, $sql, $params)) {
            throw $this->getError('Query failed');
        }

        return new Statement($stmt, true);
    }

    /**
     * Prepares and executes a single SQL Server query with bound parameters
     * @throws SqlException if an error occurs
     */
    public function query(string $sql, array $params = []): Statement
    {
        if (!$stmt = sqlsrv_query($this->conn, $sql, $params)) {
            throw $this->getError('Query failed');
        }

        $statement = new Statement($stmt, false);
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
