<?php

namespace PeachySQL\Mysql;

use mysqli_stmt;
use PeachySQL\BaseStatement;
use PeachySQL\SqlException;

class Statement extends BaseStatement
{
    private $insertId;
    private $stmt;
    private $meta;

    public function __construct(mysqli_stmt $stmt, $usedPrepare, $query, array $params)
    {
        parent::__construct($usedPrepare, $query, $params);
        $this->stmt = $stmt;
    }

    public function execute()
    {
        if (!$this->stmt->execute()) {
            throw new SqlException('Failed to execute prepared statement',
                $this->stmt->error_list, $this->query, $this->params);
        }

        $this->affected = $this->stmt->affected_rows;
        $this->insertId = $this->stmt->insert_id; // id of first inserted row, otherwise 0;
        $this->meta = $this->stmt->result_metadata();

        if (!$this->usedPrepare && !$this->meta) {
            $this->close(); // no results, so statement can be closed
        }
    }

    /**
     * Returns the first insert ID for the query, from mysqli_stmt::$insert_id
     * @return int
     */
    public function getInsertId()
    {
        return $this->insertId;
    }

    public function getIterator()
    {
        if ($this->stmt !== null) {
            // retrieve selected rows without depending on mysqlnd-only methods such as get_result
            $fields = [];
            $rowData = [];

            // bind_result() must be passed an argument by reference for each field
            while ($field = $this->meta->fetch_field()) {
                $fields[] = &$rowData[$field->name];
            }

            $this->meta->free();

            if (!call_user_func_array([$this->stmt, 'bind_result'], $fields)) {
                throw new SqlException('Failed to bind results', $this->stmt->error_list, $this->query, $this->params);
            }

            while ($this->stmt->fetch()) {
                // loop through all the fields and values to prevent
                // PHP from just copying the same $rowData reference (see
                // http://www.php.net/manual/en/mysqli-stmt.bind-result.php#92505).
                $row = [];

                foreach ($rowData as $k => $v) {
                    $row[$k] = $v;
                }

                yield $row;
            }

            if (!$this->usedPrepare) {
                $this->close();
            }
        }
    }

    /**
     * Closes the prepared statement and deallocates the statement handle.
     * @throws SqlException if failure closing the statement
     */
    public function close()
    {
        if (!$this->stmt->close()) {
            throw new SqlException('Failed to close statement', $this->stmt->error_list, $this->query, $this->params);
        }

        $this->stmt = null;
        $this->meta = null;
    }
}
