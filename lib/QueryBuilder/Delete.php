<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

/**
 * Class used for delete query generation
 */
class Delete extends Query
{
    /**
     * Generates a delete query with where clause for the specified table.
     */
    public function buildQuery(string $table, array $where): SqlParams
    {
        $whereClause = $this->buildWhereClause($where);
        $sql = "DELETE FROM {$table}" . $whereClause->getSql();
        return new SqlParams($sql, $whereClause->getParams());
    }
}
