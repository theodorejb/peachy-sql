<?php

namespace PeachySQL;

/**
 * Contains rows, affected count, and the query string for a completed SQL query
 */
class SQLResult {

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
    public function __construct(array $rows, $affected, $query) {
        $this->rows = $rows;
        $this->affected = $affected;
        $this->query = $query;
    }

    /**
     * Returns an array of rows selected by the query
     * @return array
     */
    public function getRows() {
        return $this->rows;
    }

    /**
     * Returns the number of rows affected by the query
     * @return int
     */
    public function getAffected() {
        return $this->affected;
    }

    /**
     * Returns the SQL query
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }

}
