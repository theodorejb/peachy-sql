<?php

namespace PeachySQL;

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
    const OPT_AUTO_INCREMENT_INCREMENT = "autoIncrementIncrement";

    /**
     * The connection used to access the database
     * @var \mysqli
     */
    private $connection;

    /**
     * Default MySQL-specific options
     * @var array
     */
    private $mysqlOptions = [
        self::OPT_AUTO_INCREMENT_INCREMENT => 1,
    ];

    /**
     * @param \mysqli $connection A mysqli connection instance
     * @param array $options Options used when querying the database
     */
    public function __construct(\mysqli $connection, array $options = [])
    {
        $this->setConnection($connection);
        $this->setOptions($options);
    }

    /**
     * Easily switch to a different mysqli database connection
     * @param \mysqli $connection
     */
    public function setConnection(\mysqli $connection)
    {
        if (!$connection instanceof \mysqli) {
            throw new \InvalidArgumentException('Connection must be a mysqli instance');
        }

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
        // begin_transaction is new in PHP 5.5
        if (method_exists($this->connection, 'begin_transaction')) {
            if (!$this->connection->begin_transaction()) {
                throw new SqlException("Failed to begin transaction", $this->connection->error_list);
            }
        } elseif (!$this->connection->query('BEGIN;')) {
            throw new SqlException("Failed to begin transaction", $this->connection->error_list);
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function commit()
    {
        if (!$this->connection->commit()) {
            throw new SqlException("Failed to commit transaction", $this->connection->error_list);
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SqlException if an error occurs
     */
    public function rollback()
    {
        if (!$this->connection->rollback()) {
            throw new SqlException("Failed to roll back transaction", $this->connection->error_list);
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
            throw new SqlException("Failed to prepare statement", $this->connection->error_list, $sql, $params);
        }

        if (!empty($params)) {
            $typesValues = [self::getMysqlParamTypes($params)];

            // so that call_user_func_array will pass by reference
            foreach ($params as &$param) {
                $typesValues[] = &$param;
            }

            if (!call_user_func_array(array($stmt, 'bind_param'), $typesValues)) {
                throw new SqlException("Failed to bind params", $stmt->error_list, $sql, $params);
            }
        }

        if (!$stmt->execute()) {
            throw new SqlException("Failed to execute prepared statement", $stmt->error_list, $sql, $params);
        }

        $insertId = $stmt->insert_id; // id of first inserted row, otherwise 0
        $affected = $stmt->affected_rows;
        $rows = [];

        // Selected rows must be retrieved as an array without depending on the
        // mysqlnd-only get_result() method. Hence the following "hackish" code.
        $meta = $stmt->result_metadata();

        if ($meta) {
            // results, yay!
            $fields = [];
            $rowData = [];

            // bind_result() must be passed an argument by reference for each field
            while ($field = $meta->fetch_field()) {
                $fields[] = &$rowData[$field->name];
            }

            if (!call_user_func_array(array($stmt, 'bind_result'), $fields)) {
                throw new SqlException("Failed to bind results", $stmt->error_list, $sql, $params);
            }

            $i = 0;
            while ($stmt->fetch()) {
                // loop through all the fields and values to prevent
                // PHP from just copying the same $rowData reference (see
                // http://www.php.net/manual/en/mysqli-stmt.bind-result.php#92505).

                foreach ($rowData as $k => $v) {
                    $rows[$i][$k] = $v;
                }

                $i++;
            }

            $meta->free();
        }

        $stmt->close();
        return new MysqlResult($rows, $affected, $sql, $insertId);
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
     * @param  array  $params
     * @return string A string containing the type character for each parameter
     */
    private static function getMysqlParamTypes(array $params)
    {
        // just treat all the parameters as strings since mysql "automatically 
        // converts strings to the column's actual datatype when processing 
        // queries" (see http://stackoverflow.com/a/14370546/1170489).

        return str_repeat("s", count($params));
    }
}
