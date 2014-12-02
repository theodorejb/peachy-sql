<?php

namespace PeachySQL;

/**
 * Object returned when performing bulk insert queries
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class BulkInsertResult
{
    /**
     * @var int[]
     */
    private $ids;

    /**
     * @var int
     */
    private $affected;

    /**
     * @var int
     */
    private $queryCount;

    /**
     * @param int[] $ids
     * @param int $affected
     * @param int $queryCount
     */
    public function __construct(array $ids, $affected, $queryCount = 1)
    {
        $this->ids = $ids;
        $this->affected = $affected;
        $this->queryCount = $queryCount;
    }

    /**
     * Returns the IDs of the inserted rows
     * @return int[]
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * Returns the number of affected rows
     * @return int
     */
    public function getAffected()
    {
        return $this->affected;
    }

    /**
     * Returns the number of individual queries used to perform the bulk insert
     * @return int
     */
    public function getQueryCount()
    {
        return $this->queryCount;
    }
}
