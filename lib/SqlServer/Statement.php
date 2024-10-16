<?php

declare(strict_types=1);

namespace PeachySQL\SqlServer;

use PeachySQL\BaseStatement;
use PeachySQL\SqlException;
use PeachySQL\SqlServer;

class Statement extends BaseStatement
{
    /** @var resource */
    private $stmt;

    /**
     * @param resource $stmt
     */
    public function __construct($stmt, bool $usedPrepare)
    {
        parent::__construct($usedPrepare);
        $this->stmt = $stmt;
    }

    public function execute(): void
    {
        if ($this->usedPrepare) {
            if (!sqlsrv_execute($this->stmt)) {
                throw SqlServer::getError('Failed to execute prepared statement');
            }
        }

        /** @var bool|null $next */
        $next = null;
        $this->affected = -1;

        // get affected row count from each result (triggers could cause multiple inserts)
        do {
            $affectedRows = sqlsrv_rows_affected($this->stmt);

            if ($affectedRows === false) {
                throw SqlServer::getError('Failed to get affected row count');
            }

            if ($affectedRows === -1) {
                // reached SELECT result
                break; // so that getIterator will be able to select the rows
            }

            if ($this->affected === -1) {
                $this->affected = 0;
            }

            $this->affected += $affectedRows;
        } while ($next = sqlsrv_next_result($this->stmt));

        if ($next === false) {
            throw SqlServer::getError('Failed to get next result');
        }

        if ($affectedRows !== -1 && !$this->usedPrepare) {
            $this->close(); // no results, so statement can be closed
        }
    }

    public function getIterator(): \Generator
    {
        // only fetch rows if the statement is open
        /** @var mixed $this->stmt */
        if (is_resource($this->stmt)) {
            while ($row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC)) {
                yield $row;
            }

            if (!$this->usedPrepare) {
                $this->close();
            }
        }
    }

    /**
     * Frees all resources associated with the result statement.
     * @throws SqlException if failure closing the statement
     */
    public function close(): void
    {
        if (!sqlsrv_free_stmt($this->stmt)) {
            throw SqlServer::getError('Failed to close statement');
        }
    }
}
