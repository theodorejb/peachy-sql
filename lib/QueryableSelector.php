<?php

namespace PeachySQL;

use PeachySQL\QueryBuilder\Selector;

class QueryableSelector extends Selector
{
    private $peachySql;

    public function __construct($query, PeachySql $peachySql)
    {
        parent::__construct($query, $peachySql->getOptions());
        $this->peachySql = $peachySql;
    }

    /**
     * @return BaseStatement
     */
    public function query()
    {
        $sqlParams = $this->getSqlParams();
        return $this->peachySql->query($sqlParams->getSql(), $sqlParams->getParams());
    }
}
