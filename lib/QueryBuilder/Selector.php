<?php

namespace PeachySQL\QueryBuilder;

use PeachySQL\BaseOptions;

class Selector
{
    private $query;
    private $options;

    private $where = [];
    private $orderBy = [];
    private $limit;
    private $offset;

    /**
     * @param string $query
     * @param BaseOptions $options
     */
    public function __construct($query, BaseOptions $options)
    {
        $this->query = $query;
        $this->options = $options;
    }

    /**
     * @param array $filter
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
     * @param int $maximum
     * @return $this
     * @throws \Exception if a parameter is invalid
     */
    public function offset($offset, $limit, $maximum = 1000)
    {
        if ($maximum < 1) {
            throw new \Exception('Maximum must be greater than zero');
        } elseif ($limit < 1) {
            throw new \Exception('Limit must be greater than zero');
        } elseif ($limit > $maximum) {
            throw new \Exception("Limit cannot exceed {$maximum}");
        } elseif ($offset < 0) {
            throw new \Exception('Offset cannot be negative');
        }

        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @param int $maximum
     * @return $this
     * @deprecated
     */
    public function paginate($page, $pageSize, $maximum = 1000)
    {
        return $this->offset(($page - 1) * $pageSize, $pageSize, $maximum);
    }

    /**
     * @return SqlParams
     * @throws \Exception if attempting to paginate unordered rows
     */
    public function getSqlParams()
    {
        $select = new Select($this->options);
        $where = $select->buildWhereClause($this->where);
        $orderBy = $select->buildOrderByClause($this->orderBy);
        $sql = $this->query . $where->getSql() . $orderBy;

        if ($this->limit !== null && $this->offset !== null) {
            if ($this->orderBy === []) {
                throw new \Exception('Results must be sorted to use an offset');
            }

            $sql .= ' ' . $select->buildPagination($this->limit, $this->offset);
        }

        return new SqlParams($sql, $where->getParams());
    }
}
