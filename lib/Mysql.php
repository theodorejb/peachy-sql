<?php

namespace PeachySQL;

use mysqli;
use PeachySQL\QueryBuilder\Insert;

/**
 * Implements the standard PeachySQL features for MySQL (using mysqli)
 * 
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Mysql extends PeachySql
{
    /**
     * Options key for specifying the interval between successive auto-incremented 
     * values in the table (used to retrieve array of insert IDs for bulk inserts)
     */
    const OPT_AUTO_INCREMENT_INCREMENT = 'autoIncrementIncrement';

    /**
     * The connection used to access the database
     * @var mysqli
     */
    private $connection;

    /**
     * Default MySQL-specific options
     * @var array
     */
    private $mysqlOptions = [
        self::OPT_AUTO_INCREMENT_INCREMENT => 1,
        self::OPT_MAX_PARAMS               => 65536, // 2^16
        self::OPT_MAX_INSERT_ROWS          => null,
    ];

    /**
     * @param mysqli $connection A mysqli connection instance
     * @param array  $options    Array of PeachySQL options
     */
    public function __construct(mysqli $connection, array $options = [])
    {
        $this->setConnection($connection);
        $this->setOptions($options);
    }

    /**
     * Easily switch to a different mysqli database connection
     * @param mysqli $connection
     */
    public function setConnection(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set options used to select, insert, update, and delete from the database
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->mysqlOptions, $this->options);
        parent::setOptions($options);
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
     * Executes a single MySQL query
     *
     * @param string $sql
     * @param array  $params Values to bind to placeholders in the query string
     * @return MysqlResult
     * @throws SqlException if an error occurs
     */
    public function query($sql, array $params = [])
    {
        // prepare the statement
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
            foreach ($params as &$param) {
                $typesValues[] = &$param;
            }

            if (!call_user_func_array([$stmt, 'bind_param'], $typesValues)) {
                throw new SqlException('Failed to bind params', $stmt->error_list, $sql, $params);
            }
        }

        if (!$stmt->execute()) {
            throw new SqlException('Failed to execute prepared statement', $stmt->error_list, $sql, $params);
        }

        return new MysqlResult($stmt, $sql, $params);
    }

    /**
     * Performs a single bulk insert query
     * @param array $colVals
     * @return BulkInsertResult
     */
    protected function insertBatch(array $colVals)
    {
        $query = Insert::buildQuery($this->options[self::OPT_TABLE], $colVals, $this->options[self::OPT_COLUMNS]);
        $result = $this->query($query['sql'], $query['params']);
        $firstId = $result->getInsertId(); // ID of first inserted row, or zero if no insert ID

        if ($firstId) {
            $lastId = $firstId + count($colVals) - 1;
            $ids = range($firstId, $lastId, $this->options[self::OPT_AUTO_INCREMENT_INCREMENT]);
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
