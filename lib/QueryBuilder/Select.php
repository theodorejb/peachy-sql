<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for select query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Select extends Query
{
    /**
     * @param array $orderBy
     * @return string
     * @throws \Exception if there is an invalid sort direction
     */
    public function buildOrderByClause(array $orderBy)
    {
        if (empty($orderBy)) {
            return '';
        }

        $sql = ' ORDER BY ';

        // [column1, column2, ...]
        if (isset($orderBy[0])) {
            $orderBy = $this->escapeColumns($orderBy);
            return $sql . implode(', ', $orderBy);
        }

        // [column1 => direction, column2 => direction, ...]
        foreach ($orderBy as $column => $direction) {
            $column = $this->options->escapeIdentifier($column);
            $sql .= $column;

            if ($direction === 'asc') {
                $sql .= ' ASC, ';
            } elseif ($direction === 'desc') {
                $sql .= ' DESC, ';
            } else {
                throw new \Exception("{$direction} is not a valid sort direction for column {$column}. Use asc or desc.");
            }
        }

        return substr_replace($sql, '', -2); // remove trailing comma and space
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function buildPagination($limit, $offset)
    {
        if ($this->options instanceof \PeachySQL\SqlServer\Options) {
            return "OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
        } else {
            return "LIMIT {$limit} OFFSET {$offset}";
        }
    }
}
