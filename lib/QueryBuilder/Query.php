<?php

namespace PeachySQL\QueryBuilder;
use PeachySQL\BaseOptions;

/**
 * Base class used for query generation and validation
 */
abstract class Query
{
    protected $options;

    public function __construct(BaseOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @param string[] $columns
     * @return string[]
     */
    protected function escapeColumns(array $columns)
    {
        return array_map([$this->options, 'escapeIdentifier'], $columns);
    }

    /**
     * @param array    $columnVals An associative array of columns and values to filter rows.
     *                             E.g. ["id" => 3] to only return rows where id is equal to 3.
     *                             If the value is an array, an IN(...) clause will be used.
     * @return SqlParams
     */
    protected function buildWhereClause(array $columnVals)
    {
        if (empty($columnVals)) {
            return new SqlParams('', []);
        }

        $sql = ' WHERE';
        $params = [];

        foreach ($columnVals as $column => $value) {
            if ($value === null) {
                $comparison = 'IS NULL';
            } elseif (is_array($value)) {
                // use IN(...) syntax
                $comparison = substr_replace('IN(' . str_repeat('?,', count($value)), ')', -1); // replace trailing comma
                $params = array_merge($params, $value);
            } else {
                $comparison = '= ?';
                $params[] = $value;
            }

            $sql .= ' ' . $this->options->escapeIdentifier($column) . " $comparison AND";
        }

        $sql = substr_replace($sql, '', -4); // remove the trailing AND
        return new SqlParams($sql, $params);
    }
}
