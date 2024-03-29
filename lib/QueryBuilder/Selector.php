<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

use PeachySQL\BaseOptions;

/**
 * @psalm-import-type WhereClause from Query
 */
class Selector
{
    private SqlParams $query;
    private BaseOptions $options;

    /** @var WhereClause */
    private array $where = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(SqlParams $query, BaseOptions $options)
    {
        $this->query = $query;
        $this->options = $options;
    }

    /**
     * @param WhereClause $filter
     * @return $this
     * @throws \Exception if called more than once
     */
    public function where(array $filter)
    {
        if ($this->where !== []) {
            throw new \Exception('where method can only be called once');
        }

        $this->where = $filter;
        return $this;
    }

    /**
     * @param array $sort
     * @return $this
     * @throws \Exception if called more than once
     */
    public function orderBy(array $sort)
    {
        if ($this->orderBy !== []) {
            throw new \Exception('orderBy method can only be called once');
        }

        $this->orderBy = $sort;
        return $this;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return $this
     * @throws \Exception if a parameter is invalid
     */
    public function offset(int $offset, int $limit)
    {
        if ($limit < 1) {
            throw new \Exception('Limit must be greater than zero');
        } elseif ($offset < 0) {
            throw new \Exception('Offset cannot be negative');
        }

        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * @throws \Exception if attempting to paginate unordered rows
     */
    public function getSqlParams(): SqlParams
    {
        $select = new Select($this->options);
        $where = $select->buildWhereClause($this->where);
        $orderBy = $select->buildOrderByClause($this->orderBy);
        $sql = $this->query->getSql() . $where->getSql() . $orderBy;

        if ($this->limit !== null && $this->offset !== null) {
            if ($this->orderBy === []) {
                throw new \Exception('Results must be sorted to use an offset');
            }

            $sql .= ' ' . $select->buildPagination($this->limit, $this->offset);
        }

        return new SqlParams($sql, [...$this->query->getParams(), ...$where->getParams()]);
    }
}
