<?php

namespace PeachySQL;

/**
 * Contains rows, affected count, and the query string for a completed SQL query
 */
class SqlResult
{
    /**
     * An array of rows selected in the query
     * @var array
     */
    private $rows;

    /**
     * The number of rows affected by the query
     * @var int
     */
    private $affected;

    /**
     * The SQL query
     * @var string
     */
    private $query;

    /**
     * @param array  $rows
     * @param int    $affected
     * @param string $query
     */
    public function __construct(array $rows, $affected, $query)
    {
        $this->rows = $rows;
        $this->affected = $affected;
        $this->query = $query;
    }

    /**
     * Returns all rows selected by the query
     * @return array
     */
    public function getAll()
    {
        return $this->rows;
    }

    /**
     * Returns the first selected row, or null if zero rows were returned
     * @return array
     */
    public function getFirst()
    {
        return empty($this->rows) ? null : $this->rows[0];
    }

    /**
     * Returns the number of rows affected by the query
     * @return int
     */
    public function getAffected()
    {
        return $this->affected;
    }

    /**
     * Returns the SQL query
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }
}
