<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Object returned when inserting a single row
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class InsertResult
{
    private $id;
    private $affected;

    public function __construct(int $id, int $affected)
    {
        $this->id = $id;
        $this->affected = $affected;
    }

    /**
     * Returns the ID of the inserted row
     * @throws \Exception if the row doesn't have an auto-incremented ID
     */
    public function getId(): int
    {
        if ($this->id === 0) {
            throw new \Exception('Inserted row does not have an auto-incremented ID');
        }

        return $this->id;
    }

    /**
     * Returns the number of affected rows
     */
    public function getAffected(): int
    {
        return $this->affected;
    }
}
