<?php

namespace PeachySQL\QueryBuilder;

use PeachySQL\BaseOptions;

/**
 * Base class used for query generation and validation
 */
class Query
{
    protected $options;

    private static $operatorMap = [
        'eq' => '=',
        'ne' => '<>',
        'lt' => '<',
        'le' => '<=',
        'gt' => '>',
        'ge' => '>=',
        'lk' => 'LIKE',
        'nl' => 'NOT LIKE',
    ];

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
     * @param array $columnVals
     * @return SqlParams
     * @throws \Exception if a column filter is empty
     */
    public function buildWhereClause(array $columnVals)
    {
        if (empty($columnVals)) {
            return new SqlParams('', []);
        }

        $conditions = $params = [];

        foreach ($columnVals as $column => $value) {
            $column = $this->options->escapeIdentifier($column);

            if ($value === null) {
                $conditions[] =  "{$column} IS NULL";
                continue;
            }

            if (!is_array($value)) {
                $conditions[] = "{$column} = ?";
                $params[] = $value;
                continue;
            }

            if (count($value) === 0) {
                throw new \Exception("Filter conditions cannot be empty for {$column} column");
            } elseif (isset($value[0])) {
                // same as eq operator with array - handle below
                $value = ['eq' => $value];
            }

            foreach ($value as $shorthand => $val) {
                if (!isset(self::$operatorMap[$shorthand])) {
                    throw new \Exception("{$shorthand} is not a valid operator");
                }

                if (!is_array($val)) {
                    $comparison = self::$operatorMap[$shorthand];
                    $conditions[] = "{$column} {$comparison} ?";
                    $params[] = $val;
                    continue;
                }

                if ($shorthand === 'eq' || $shorthand === 'ne') {
                    // use IN(...) syntax
                    $conditions[] = $column . ($shorthand === 'ne' ? ' NOT IN(' : ' IN(')
                        . str_repeat('?,', count($val) - 1) . '?)';
                    $params = array_merge($params, $val);
                } elseif ($shorthand === 'nl') {
                    foreach ($val as $notLike) {
                        $conditions[] = $column . ' NOT LIKE ?';
                        $params[] = $notLike;
                    }
                } else {
                    throw new \Exception("{$shorthand} operator cannot be used with an array");
                }
            }
        }

        return new SqlParams(' WHERE ' . implode(' AND ', $conditions), $params);
    }
}
