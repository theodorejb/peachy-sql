<?php

namespace PeachySQL;

/**
 * Same as SQLResult, but with an insert ID parameter and getter method
 */
class MySQLResult extends SQLResult
{
    private $insertId;

    /**
     * @param array  $rows
     * @param int    $affected
     * @param string $query
     * @param int    $firstInsertId
     */
    public function __construct(array $rows, $affected, $query, $firstInsertId)
    {
        parent::__construct($rows, $affected, $query);
        $this->insertId = $firstInsertId;
    }

    /**
     * Returns the first insert ID for the query, from mysqli_stmt::$insert_id
     * @return int
     */
    public function getFirstInsertId()
    {
        return $this->insertId;
    }
}
