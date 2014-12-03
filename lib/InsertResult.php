<?php

namespace PeachySQL;

/**
 * Object returned when inserting a single row
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class InsertResult
{
    private $id;
    private $affected;

    /**
     * @param int $id
     * @param int $affected
     */
    public function __construct($id, $affected)
    {
        $this->id = $id;
        $this->affected = $affected;
    }

    /**
     * Returns the ID of the inserted row
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
