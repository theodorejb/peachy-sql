<?php

namespace PeachySQL;

class SqlServerResult extends SqlResult
{
    private $stmt;

    /**
     * @param resource $stmt
     * @param string $query
     * @param array $params
     * @throws SqlException if error getting affected rows or next result
     */
    public function __construct($stmt, $query, array $params)
    {
        $next = null;
        $selectedRows = false;
        $affected = 0;

        // get affected row count from each result (triggers could cause multiple inserts)
        do {
            $affectedRows = sqlsrv_rows_affected($stmt);

            if ($affectedRows === false) {
                throw new SqlException('Failed to get affected row count', sqlsrv_errors(), $query, $params);
            } elseif ($affectedRows === -1) {
                $selectedRows = true; // reached SELECT result
                break; // so that getIterator will be able to select the rows
            } else {
                $affected += $affectedRows;
            }
        } while ($next = sqlsrv_next_result($stmt));

        if ($next === false) {
            throw new SqlException('Failed to get next result', sqlsrv_errors(), $query, $params);
        }

        parent::__construct($affected, $query, $params);
        $this->stmt = $stmt;

        if ($selectedRows === false) {
            $this->close(); // no results, so statement can be closed
        }
    }

    /**
     * Returns an iterator which an be used to loop through each row in the result
     * @return \Generator
     */
    public function getIterator()
    {
        if ($this->stmt !== null) {
            while ($row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC)) {
                yield $row;
            }

            $this->close();
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
