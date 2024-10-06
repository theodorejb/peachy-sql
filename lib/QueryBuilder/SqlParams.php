<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

/**
 * Represents a SQL query and its corresponding parameters
 * @readonly
 */
class SqlParams
{
    public string $sql;
    /** @var list<mixed> */
    public array $params;

    /**
     * @param list<mixed> $params
     */
    public function __construct(string $sql, array $params)
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * @deprecated Use readonly property instead
     * @api
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return list<mixed>
     * @deprecated Use readonly property instead
     * @api
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
