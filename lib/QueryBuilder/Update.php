<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for update query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Update extends Query
{
    /**
     * Generates an update query using the specified set/where arrays
     *
     * @param string $table
     * @param array    $set       An array of columns/values to update
     * @param array    $where     An array of columns/values to restrict the update to
     * @return SqlParams
     * @throws \Exception if the $set or $where arrays are empty
     */
    public function buildQuery($table, array $set, array $where)
    {
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
