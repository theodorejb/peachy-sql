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
     * @param int[] $ids
     * @param int $affected
     */
    public function __construct(array $ids, $affected)
    {
        $this->ids = $ids;
        $this->affected = $affected;
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
}
