<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Object returned when inserting a single row
 * @readonly
 */
class InsertResult
{
    /**
     * The ID of the inserted row (0 if the row doesn't have an auto-incremented ID)
     */
    public int $id;

    /**
     * The number of affected rows
     */
    public int $affected;

    public function __construct(int $id, int $affected)
    {
        $this->id = $id;
        $this->affected = $affected;
    }

    /**
     * Returns the ID of the inserted row
     * @throws \Exception if the row doesn't have an auto-incremented ID
     * @deprecated Use readonly property instead
     * @api
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
     * @deprecated Use readonly property instead
     * @api
     */
    public function getAffected(): int
    {
        return $this->affected;
    }
}
