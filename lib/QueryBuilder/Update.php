<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

/**
 * Class used for update query generation
 * @psalm-import-type WhereClause from Query
 */
class Update extends Query
{
    /**
     * Generates an update query using the specified set/where arrays
     * @throws \Exception if the $set or $where arrays are empty
     * @psalm-param WhereClause $where
     */
    public function buildQuery(string $table, array $set, array $where): SqlParams
    {
        /** @var array<string, int|float|bool|string> $set */

        if (empty($set) || empty($where)) {
            throw new \Exception('Set and where arrays cannot be empty');
        }

        $params = [];
        $sql = "UPDATE {$table} SET ";

        foreach ($set as $column => $value) {
            $sql .= $this->options->escapeIdentifier($column) . ' = ?, ';
            $params[] = $value;
        }

        $sql = substr_replace($sql, '', -2); // remove trailing comma
        $whereClause = $this->buildWhereClause($where);
        $sql .= $whereClause->getSql();

        return new SqlParams($sql, array_merge($params, $whereClause->getParams()));
    }
}
