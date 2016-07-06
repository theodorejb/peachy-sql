<?php

namespace PeachySQL;

use mysqli;
use PeachySQL\Mysql\Options;
use PeachySQL\Mysql\Statement;
use PeachySQL\QueryBuilder\Insert;

/**
 * Implements the standard PeachySQL features for MySQL (using mysqli)
 * 
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Mysql extends PeachySql
{
    /**
     * The connection used to access the database
     * @var mysqli
     */
    private $connection;
    private $usedPrepare;

    public function __construct(mysqli $connection, Options $options = null)
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
    public function begin()
    {
        if (!$this->connection->begin_transaction()) {
            throw new SqlException('Failed to begin transaction', $this->connection->error_list);
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit()
    {
        if (!$this->connection->commit()) {
            throw new SqlException('Failed to commit transaction', $this->connection->error_list);
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback()
    {
        if (!$this->connection->rollback()) {
            throw new SqlException('Failed to roll back transaction', $this->connection->error_list);
        }
    }

    /**
     * Returns a prepared statement which can be executed multiple times
     * @param string $sql
     * @param array $params
     * @return Statement
     * @throws SqlException if an error occurs
     */
    public function prepare($sql, array $params = [])
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
            $typesValues = [self::getMysqlParamTypes($params)];

            // so that call_user_func_array will pass by reference
            // argument unpacking can't be used due to https://github.com/facebook/hhvm/issues/6229
            foreach ($params as &$param) {
                $typesValues[] = &$param;
            }

            if (!call_user_func_array([$stmt, 'bind_param'], $typesValues)) {
                throw new SqlException('Failed to bind params', $stmt->error_list, $sql, $params);
            }
        }

        return new Statement($stmt, $this->usedPrepare, $sql, $params);
    }

    /**
     * Prepares and executes a single MySQL query
     * @param string $sql
     * @param array  $params Values to bind to placeholders in the query string
     * @return Statement
     */
    public function query($sql, array $params = [])
    {
        $this->usedPrepare = false;
        $stmt = $this->prepare($sql, $params);
        $this->usedPrepare = true;
        $stmt->execute();
        return $stmt;
    }

    /**
     * Performs a single bulk insert query
     * @param string $table
     * @param array $colVals
     * @param int $identityIncrement
     * @return BulkInsertResult
     */
    protected function insertBatch($table, array $colVals, $identityIncrement = 1)
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
     * 
     * @param array $params
     * @return string A string containing the type character for each parameter
     */
    private static function getMysqlParamTypes(array $params)
    {
        // just treat all the parameters as strings since mysql "automatically 
        // converts strings to the column's actual datatype when processing 
        // queries" (see http://stackoverflow.com/a/14370546/1170489).

        return str_repeat('s', count($params));
    }
}
