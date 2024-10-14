<?php

declare(strict_types=1);

namespace PeachySQL\Mysql;

use mysqli_stmt;
use PeachySQL\BaseStatement;
use PeachySQL\SqlException;

class Statement extends BaseStatement
{
    private int $insertId = 0;
    private ?mysqli_stmt $stmt;

    public function __construct(mysqli_stmt $stmt, bool $usedPrepare)
    {
        parent::__construct($usedPrepare);
        $this->stmt = $stmt;
    }

    public function execute(): void
    {
        if ($this->stmt === null) {
            throw new \Exception('Cannot execute closed statement');
        }

        try {
            if (!$this->stmt->execute()) {
                throw $this->getError('Failed to execute prepared statement');
            }
        } catch (\mysqli_sql_exception $e) {
            throw $this->getError('Failed to execute prepared statement');
        }

        $this->affected = (int)$this->stmt->affected_rows;
        $this->insertId = (int)$this->stmt->insert_id; // id of first inserted row, otherwise 0;

        if (!$this->usedPrepare && !$this->stmt->result_metadata()) {
            $this->close(); // no results, so statement can be closed
        }
    }

    private function getError(string $message): SqlException
    {
        if ($this->stmt === null) {
            throw new \Exception('Cannot get error info for closed statement');
        }
        return new SqlException($message, $this->stmt->errno, $this->stmt->error, $this->stmt->sqlstate);
    }

    /**
     * Returns the first insert ID for the query, from mysqli_stmt::$insert_id
     */
    public function getInsertId(): int
    {
        return $this->insertId;
    }

    public function getIterator(): \Generator
    {
        if ($this->stmt !== null) {
            $result = $this->stmt->get_result();

            if (!$result) {
                throw $this->getError('Failed to get result');
            }

            while ($row = $result->fetch_assoc()) {
                yield $row;
            }

            if (!$this->usedPrepare) {
                $this->close();
            }
        }
    }

    /**
     * Closes the prepared statement and deallocates the statement handle.
     * @throws \Exception if the statement has already been closed
     */
    public function close(): void
    {
        if ($this->stmt === null) {
            throw new \Exception('Statement has already been closed');
        }

        $this->stmt->close();
        $this->stmt = null;
    }
}
