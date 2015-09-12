<?php

namespace PeachySQL\QueryBuilder;

/**
 * Represents a SQL query and its corresponding parameters
 */
class SqlParams
{
    private $sql;
    private $params;

    /**
     * @param string $sql
     * @param array $params
     */
    public function __construct($sql, array $params)
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getParams()
    {
        return $this->params;
    }
}
