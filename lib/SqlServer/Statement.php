<?php

namespace PeachySQL\SqlServer;

use PeachySQL\BaseStatement;
use PeachySQL\SqlException;

class Statement extends BaseStatement
{
    private $stmt;

    public function __construct($stmt, $usedPrepare, $query, array $params)
    {
        parent::__construct($usedPrepare, $query, $params);
        $this->stmt = $stmt;
    }

    public function execute()
    {
        if ($this->usedPrepare) {
            if (!sqlsrv_execute($this->stmt)) {
                throw new SqlException('Failed to execute prepared statement', sqlsrv_errors(), $this->query, $this->params);
            }
        }

        $next = null;
        $selectedRows = false;
        $this->affected = 0;

        // get affected row count from each result (triggers could cause multiple inserts)
        do {
            $affectedRows = sqlsrv_rows_affected($this->stmt);

            if ($affectedRows === false) {
                throw new SqlException('Failed to get affected row count', sqlsrv_errors(), $this->query, $this->params);
            } elseif ($affectedRows === -1) {
                $selectedRows = true; // reached SELECT result
                break; // so that getIterator will be able to select the rows
            } else {
                $this->affected += $affectedRows;
            }
        } while ($next = sqlsrv_next_result($this->stmt));

        if ($next === false) {
            throw new SqlException('Failed to get next result', sqlsrv_errors(), $this->query, $this->params);
        }

        if ($selectedRows === false && !$this->usedPrepare) {
            $this->close(); // no results, so statement can be closed
        }
    }

    public function getIterator()
    {
        if ($this->stmt !== null) {
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
    public function close()
    {
        if (!sqlsrv_free_stmt($this->stmt)) {
            throw new SqlException('Failed to close statement', sqlsrv_errors(), $this->query, $this->params);
        }
    }
}
