<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Object returned when performing bulk insert queries
 * @readonly
 */
class BulkInsertResult
{
    /**
     * The IDs of the inserted rows
     * @var list<int>
     */
    public array $ids;

    /**
     * The number of affected rows
     */
    public int $affected;

    /**
     * The number of individual queries used to perform the bulk insert
     */
    public int $queryCount;

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
     * @deprecated Use readonly property instead
     * @return list<int>
     * @api
     */
    public function getIds(): array
    {
        return $this->ids;
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

    /**
     * Returns the number of individual queries used to perform the bulk insert
     * @deprecated Use readonly property instead
     * @api
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }
}
