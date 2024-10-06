<?php

declare(strict_types=1);

namespace PeachySQL;

use PeachySQL\QueryBuilder\{Selector, SqlParams};

class QueryableSelector extends Selector
{
    private PeachySql $peachySql;

    public function __construct(SqlParams $query, PeachySql $peachySql)
    {
        parent::__construct($query, $peachySql->getOptions());
        $this->peachySql = $peachySql;
    }

    public function query(): BaseStatement
    {
        $sqlParams = $this->getSqlParams();
        return $this->peachySql->query($sqlParams->sql, $sqlParams->params);
    }
}
