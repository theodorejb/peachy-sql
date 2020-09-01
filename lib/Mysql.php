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
     * @var mysqli
     */
    private $connection;
    /** @var bool */
    private $usedPrepare;

    public function __construct(mysqli $connection, ?Options $options = null)
    {
        $this->connection = $connection;
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
        if (!$this->connection->begin_transaction()) {
            throw new SqlException('Failed to begin transaction', $this->connection->error_list);
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit(): void
    {
        if (!$this->connection->commit()) {
            throw new SqlException('Failed to commit transaction', $this->connection->error_list);
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback(): void
    {
        if (!$this->connection->rollback()) {
            throw new SqlException('Failed to roll back transaction', $this->connection->error_list);
        }
    }

    public function makeBinaryParam(?string $binaryStr, ?int $length = null)
    {
        // binary values can be inserted directly when using MySQL
        return $binaryStr;
    }

    /**
     * Returns a prepared statement which can be executed multiple times
     * @throws SqlException if an error occurs
     */
    public function prepare(string $sql, array $params = []): Statement
    {
        if (!$stmt = $this->connection->prepare($sql)) {
            $error = [
                'error' => $this->connection->error,
                'errno' => $this->connection->errno,
                'sqlstate' => $this->connection->sqlstate
            ];

            throw new SqlException('Failed to prepare statement', [$error], $sql, $params);
        }

        if (!empty($params)) {
            if (!$stmt->bind_param(self::getMysqlParamTypes($params), ...$params)) {
                throw new SqlException('Failed to bind params', $stmt->error_list, $sql, $params);
            }
        }

        return new Statement($stmt, $this->usedPrepare, $sql, $params);
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
        $result = $this->query($sqlParams->getSql(), $sqlParams->getParams());
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
     * See http://php.net/manual/en/mysqli-stmt.bind-param.php.
     * Returns a string containing the type character for each parameter.
     */
    private static function getMysqlParamTypes(array $params): string
    {
        $types = '';

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
