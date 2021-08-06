<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Object returned when performing bulk insert queries
 */
class BulkInsertResult
{
    /** @var list<int> */
    private array $ids;
    private int $affected;
    private int $queryCount;

    /**
     * @param list<int> $ids
     */
    public function __construct(array $ids, int $affected, int $queryCount = 1)
    {
        $this->ids = $ids;
        $this->affected = $affected;
        $this->queryCount = $queryCount;
    }

    /**
     * Returns the IDs of the inserted rows
     * @return list<int>
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Returns the number of affected rows
     */
    public function getAffected(): int
    {
        return $this->affected;
    }

    /**
     * Returns the number of individual queries used to perform the bulk insert
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }
}
