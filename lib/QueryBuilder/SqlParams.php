<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

/**
 * Represents a SQL query and its corresponding parameters
 */
class SqlParams
{
    private string $sql;
    /** @var list */
    private array $params;

    /**
     * @param list $params
     */
    public function __construct(string $sql, array $params)
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return list
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
