<?php

declare(strict_types=1);

namespace PeachySQL;

use mysqli;
use PeachySQL\Mysql\Options;
use PeachySQL\Mysql\Statement;
use PeachySQL\QueryBuilder\Insert;

/**
 * Implements the standard PeachySQL features for MySQL (using mysqli)
 */
class Mysql extends PeachySql
{
    /**
     * The connection used to access the database
     */
    private mysqli $conn;
    private bool $usedPrepare;

    public function __construct(mysqli $connection, ?Options $options = null)
    {
        $this->conn = $connection;
        $this->usedPrepare = true;

        if ($options === null) {
            $options = new Options();
        }

        $this->options = $options;
    }

    /**
     * Begins a mysqli transaction
     * @throws SqlException if an error occurs
     */
    public function begin(): void
    {
        if (!$this->conn->begin_transaction()) {
            throw $this->getError('Failed to begin transaction');
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit(): void
    {
        if (!$this->conn->commit()) {
            throw $this->getError('Failed to commit transaction');
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback(): void
    {
        if (!$this->conn->rollback()) {
            throw $this->getError('Failed to roll back transaction');
        }
    }

    public function makeBinaryParam(?string $binaryStr, ?int $length = null): ?string
    {
        // binary values can be inserted directly when using MySQL
        return $binaryStr;
    }

    private function getError(string $message): SqlException
    {
        return new SqlException($message, $this->conn->errno, $this->conn->error, $this->conn->sqlstate);
    }

    /**
     * Returns a prepared statement which can be executed multiple times
     * @throws SqlException if an error occurs
     */
    public function prepare(string $sql, array $params = []): Statement
    {
        try {
            if (!$stmt = $this->conn->prepare($sql)) {
                throw $this->getError('Failed to prepare statement');
            }
        } catch (\mysqli_sql_exception $e) {
            throw $this->getError('Failed to prepare statement');
        }

        if ($params) {
            if (!$stmt->bind_param(self::getMysqlParamTypes($params), ...$params)) {
                /** @var array $params */
                throw new SqlException('Failed to bind params', $stmt->errno, $stmt->error, $stmt->sqlstate);
            }
        }

        /** @var array $params */
        return new Statement($stmt, $this->usedPrepare);
    }

    /**
     * Prepares and executes a single MySQL query with bound parameters
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
        $firstId = $result->getInsertId(); // ID of first inserted row, or zero if no insert ID

        if ($firstId) {
            $lastId = $firstId + $identityIncrement * (count($colVals) - 1);
            $ids = range($firstId, $lastId, $identityIncrement);
        } else {
            $ids = [];
        }

        return new BulkInsertResult($ids, $result->getAffected());
    }

    /**
     * To bind parameters in mysqli, the type of each parameter must be specified.
     * See https://www.php.net/manual/en/mysqli-stmt.bind-param.php.
     * Returns a string containing the type character for each parameter.
     */
    private static function getMysqlParamTypes(array $params): string
    {
        $types = '';
        /** @var int|float|bool|string|null $param */

        foreach ($params as $param) {
            if (is_int($param) || is_bool($param)) {
                // if boolean values are treated as strings, `false` will be cast to a blank string
                // which can cause an "Incorrect integer value: ''" error in recent MySQL versions.
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        return $types;
    }
}
