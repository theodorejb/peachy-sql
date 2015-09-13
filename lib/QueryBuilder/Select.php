<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for select query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Select extends Query
{
    /**
     * Builds a select query using the specified columns, where clause, and order by arrays
     *
     * @param string[] $columns   An array of columns to select from (all columns if empty)
     * @param array    $where     An array of columns/values to filter the select query
     * @param string[] $orderBy   One or more column names to sort by
     * @return SqlParams
     */
    public function buildQuery(array $columns = [], array $where = [], array $orderBy = [])
    {
        $whereClause = $this->buildWhereClause($where);

        if (!empty($columns)) {
            $insertCols = implode(', ', $this->escapeColumns($columns));
        } else {
            $insertCols = '*';
        }

        $sql = "SELECT $insertCols FROM " . $this->options->getTable()
            . $whereClause->getSql() . $this->buildOrderByClause($orderBy);

        return new SqlParams($sql, $whereClause->getParams());
    }

    /**
     * @param string[] $orderBy One or more column names to sort by
     * @return string
     */
    private function buildOrderByClause(array $orderBy)
    {
        if (empty($orderBy)) {
            return '';
        }

        return ' ORDER BY ' . implode(', ', $this->escapeColumns($orderBy));
    }
}
