<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

use PeachySQL\BaseOptions;

/**
 * Base class used for query generation and validation
 * @psalm-type WhereVal = int|float|bool|string|null
 * @psalm-type OptWhereList = WhereVal | list<WhereVal>
 * @psalm-type WhereClause = array<string, OptWhereList | array<string, OptWhereList>>
 */
class Query
{
    protected BaseOptions $options;

    private const OPERATOR_MAP = [
        'eq' => '=',
        'ne' => '<>',
        'lt' => '<',
        'le' => '<=',
        'gt' => '>',
        'ge' => '>=',
        'lk' => 'LIKE',
        'nl' => 'NOT LIKE',
        'nu' => 'IS NULL',
        'nn' => 'IS NOT NULL',
    ];

    public function __construct(BaseOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @param string[] $columns
     * @return string[]
     */
    protected function escapeColumns(array $columns): array
    {
        return array_map([$this->options, 'escapeIdentifier'], $columns);
    }

    /**
     * @throws \Exception if a column filter is empty
     * @param WhereClause $columnVals
     */
    public function buildWhereClause(array $columnVals): SqlParams
    {
        if (empty($columnVals)) {
            return new SqlParams('', []);
        }

        $conditions = [];
        $params = [];

        foreach ($columnVals as $column => $value) {
            $column = $this->options->escapeIdentifier($column);

            if (is_array($value) && count($value) === 0) {
                throw new \Exception("Filter conditions cannot be empty for {$column} column");
            } elseif (!is_array($value) || isset($value[0])) {
                // same as eq operator - handle below
                /** @var array<string, OptWhereList> $value */
                $value = ['eq' => $value];
            }

            foreach ($value as $shorthand => $val) {
                if (!isset(self::OPERATOR_MAP[$shorthand])) {
                    throw new \Exception("{$shorthand} is not a valid operator");
                }

                if ($val === null) {
                    throw new \Exception('Filter values cannot be null');
                } elseif ($shorthand === 'nu' || $shorthand === 'nn') {
                    if ($val !== '') {
                        throw new \Exception("{$shorthand} operator can only be used with a blank value");
                    }

                    $conditions[] = $column . ' ' . self::OPERATOR_MAP[$shorthand];
                } elseif (!is_array($val)) {
                    $comparison = self::OPERATOR_MAP[$shorthand];
                    $conditions[] = "{$column} {$comparison} ?";
                    $params[] = $val;
                } elseif ($shorthand === 'eq' || $shorthand === 'ne') {
                    // use IN(...) syntax
                    $conditions[] = $column . ($shorthand === 'ne' ? ' NOT IN(' : ' IN(')
                        . str_repeat('?,', count($val) - 1) . '?)';
                    $params = [...$params, ...$val];
                } elseif ($shorthand === 'lk' || $shorthand === 'nl') {
                    foreach ($val as $condition) {
                        $conditions[] = $column . ' ' . self::OPERATOR_MAP[$shorthand] . ' ?';
                        $params[] = $condition;
                    }
                } else {
                    // it doesn't make sense to use greater than or less than operators with multiple values
                    throw new \Exception("{$shorthand} operator cannot be used with an array");
                }
            }
        }

        return new SqlParams(' WHERE ' . implode(' AND ', $conditions), $params);
    }
}
