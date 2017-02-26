<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

/**
 * Class used for insert query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Insert extends Query
{
    /**
     * Returns the array of columns/values, split into groups containing the largest number of rows possible.
     */
    public static function batchRows(array $colVals, int $maxBoundParams, int $maxRows): array
    {
        self::validateColValsStructure($colVals);

        if ($maxBoundParams > 0) {
            $maxRowsPerQuery = (int)floor($maxBoundParams / count($colVals[0])); // max bound params divided by params per row
        } else {
            $maxRowsPerQuery = count($colVals);
        }

        if ($maxRows > 0 && $maxRowsPerQuery > $maxRows) {
            $maxRowsPerQuery = $maxRows;
        }

        return array_chunk($colVals, $maxRowsPerQuery);
    }

    /**
     * Generates an INSERT query with placeholders for values
     */
    public function buildQuery(string $table, array $colVals): SqlParams
    {
        self::validateColValsStructure($colVals);

        $columns = $this->escapeColumns(array_keys($colVals[0]));
        $insert = "INSERT INTO {$table} (" . implode(', ', $columns) . ')';

        $valSetStr = ' (' . str_repeat('?,', count($columns) - 1) . '?),';
        $valStr = ' VALUES' . substr_replace(str_repeat($valSetStr, count($colVals)), '', -1); // remove trailing comma
        $params = array_merge(...array_map('array_values', $colVals));

        if ($this->options instanceof \PeachySQL\SqlServer\Options) {
            $selStr = '; SELECT SCOPE_IDENTITY() AS RowID;';
        } else {
            $selStr = '';
        }

        return new SqlParams($insert . $valStr . $selStr, $params);
    }

    /**
     * @throws \Exception if the column/values array does not have a valid structure
     */
    private static function validateColValsStructure(array $colVals)
    {
        if (empty($colVals[0]) || !is_array($colVals[0])) {
            throw new \Exception('A valid array of columns/values to insert must be specified');
        }
    }
}
