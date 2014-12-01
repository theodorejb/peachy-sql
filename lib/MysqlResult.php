<?php

namespace PeachySQL;

/**
 * Same as SQLResult, but with an insert ID parameter and getter method
 */
class MysqlResult extends SQLResult
{
    private $insertId;

    /**
     * @param array  $rows
     * @param int    $affected
     * @param string $query
     * @param int    $insertId
     */
    public function __construct(array $rows, $affected, $query, $insertId)
    {
        parent::__construct($rows, $affected, $query);
        $this->insertId = $insertId;
    }

    /**
     * Returns the first insert ID for the query, from mysqli_stmt::$insert_id
     * @return int
     */
    public function getInsertId()
    {
        return $this->insertId;
    }
}
