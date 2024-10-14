<?php

declare(strict_types=1);

namespace PeachySQL;

use PDO;
use PeachySQL\Pgsql\Options;
use PeachySQL\Pgsql\Statement;
use PeachySQL\QueryBuilder\Insert;

/**
 * Implements the standard PeachySQL features for PostgreSQL (using PDO)
 */
class Pgsql extends PeachySql
{
    private PDO $conn;
    private bool $usedPrepare;

    public function __construct(PDO $connection, ?Options $options = null)
    {
        $this->conn = $connection;
        $this->usedPrepare = true;

        if ($options === null) {
            $options = new Options();
        }

        $this->options = $options;
    }

    /**
     * Begins a transaction
     * @throws SqlException if an error occurs
     */
    public function begin(): void
    {
        if (!$this->conn->beginTransaction()) {
            throw $this->getError('Failed to begin transaction', $this->conn->errorInfo());
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit(): void
    {
        if (!$this->conn->commit()) {
            throw $this->getError('Failed to commit transaction', $this->conn->errorInfo());
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback(): void
    {
        if (!$this->conn->rollback()) {
            throw $this->getError('Failed to roll back transaction', $this->conn->errorInfo());
        }
    }

    final public function makeBinaryParam(?string $binaryStr, ?int $length = null): array
    {
        return [$binaryStr, $length];
    }

    /**
     * @internal
     */
    public static function getError(string $message, array $error): SqlException
    {
        /** @var array{0: string, 1: int|null, 2: string|null} $error */
        $code = $error[1] ?? 0;
        $details = $error[2] ?? '';
        $sqlState = $error[0];

        return new SqlException($message, $code, $details, $sqlState);
    }

    /**
     * Returns a prepared statement which can be executed multiple times
     * @throws SqlException if an error occurs
     */
    public function prepare(string $sql, array $params = []): Statement
    {
        try {
            if (!$stmt = $this->conn->prepare($sql)) {
                throw $this->getError('Failed to prepare statement', $this->conn->errorInfo());
            }

            $i = 0;
            /** @psalm-suppress MixedAssignment */
            foreach ($params as &$param) {
                $i++;

                if (is_bool($param)) {
                    $stmt->bindParam($i, $param, PDO::PARAM_BOOL);
                } elseif (is_int($param)) {
                    $stmt->bindParam($i, $param, PDO::PARAM_INT);
                } elseif (is_array($param)) {
                    $stmt->bindParam($i, $param[0], PDO::PARAM_LOB);
                } else {
                    $stmt->bindParam($i, $param, PDO::PARAM_STR);
                }
            }
        } catch (\PDOException $e) {
            throw $this->getError('Failed to prepare statement', $this->conn->errorInfo());
        }

        return new Statement($stmt, $this->usedPrepare);
    }

    /**
     * Prepares and executes a single query with bound parameters
     */
    public function query(string $sql, array $params = []): Statement
    {
        $this->usedPrepare = false;
        $stmt = $this->prepare($sql, $params);
        $this->usedPrepare = true;
        $stmt->execute();
        return $stmt;
    }

    /**
     * Performs a single bulk insert query
     */
    protected function insertBatch(string $table, array $colVals, int $identityIncrement = 1): BulkInsertResult
    {
        $sqlParams = (new Insert($this->options))->buildQuery($table, $colVals);
        $result = $this->query($sqlParams->sql, $sqlParams->params);

        try {
            $lastId = (int) $this->conn->lastInsertId();
        } catch (\PDOException $e) {
            $lastId = 0;
        }

        if ($lastId) {
            $firstId = $lastId - $identityIncrement * (count($colVals) -1);
            $ids = range($firstId, $lastId, $identityIncrement);
        } else {
            $ids = [];
        }

        return new BulkInsertResult($ids, $result->getAffected());
    }
}
