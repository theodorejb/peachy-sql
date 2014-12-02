<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for insert query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Insert extends Query
{
    /**
     * Returns the array of columns/values, split into groups containing the largest
     * number of rows possible. Returns null if only a single insert query is required.
     *
     * @param array $colVals
     * @param int $maxParams The maximum number of bound parameters allowed per query
     * @param int $maxRows The maximum number of rows which can be inserted at once
     * @return array|null
     * @throws \Exception if $colVals isn't a valid array of columns/values
     */
    public static function batchRows(array $colVals, $maxParams, $maxRows)
    {
        self::validateColValsStructure($colVals);
        $maxRowsPerQuery = null;

        if ($maxParams !== null) {
            $maxRowsPerQuery = floor($maxParams / count($colVals[0])); // max bound params divided by params per row
        }

        if ($maxRows !== null && ($maxRowsPerQuery === null || $maxRowsPerQuery > $maxRows)) {
            $maxRowsPerQuery = $maxRows;
        }

        if ($maxRowsPerQuery === null || count($colVals) <= $maxRowsPerQuery) {
            return null; // only a single query is required
        } else {
            $group = 0;
            $batches = [];

            foreach ($colVals as $idx => $row) {
                // if the index isn't zero and it's a multiple of max rows, add a new query group
                if ($idx !== 0 && $idx % $maxRowsPerQuery === 0) {
                    $group += 1;
                }

                $batches[$group][] = $row;
            }

            return $batches;
        }
    }

    /**
     * Generates an INSERT query with placeholders for values and optional OUTPUT clause
     * @param string   $tableName The name of the table to insert into
     * @param array    $colVals   An associative array of columns/values to insert
     * @param string[] $validCols An array of valid columns
     * @param string $idCol
     * @return array
     */
    public static function buildQuery($tableName, array $colVals, array $validCols, $idCol = null)
    {
        self::validateColValsStructure($colVals);
        self::validateTableName($tableName);
        $columns = array_keys($colVals[0]);
        self::validateColumns($columns, $validCols);

        $insertCols = implode(', ', $columns);
        $insert = "INSERT INTO $tableName ($insertCols)";

        $valSetStr = substr_replace(' (' . str_repeat('?,', count($columns)), '),', -1); // replace trailing comma
        $valStr = ' VALUES' . substr_replace(str_repeat($valSetStr, count($colVals)), '', -1); // remove trailing comma
        $params = call_user_func_array('array_merge', array_map('array_values', $colVals));

        // Insert IDs must be output into a table variable so that the query will work on tables
        // with insert triggers (see http://technet.microsoft.com/en-us/library/ms177564.aspx).
        if ($idCol !== null) {
            $decStr = 'DECLARE @ids TABLE(RowID int); ';
            $outStr = " OUTPUT inserted.$idCol INTO @ids(RowID)";
            $selStr = '; SELECT * FROM @ids;';
        } else {
            $decStr = '';
            $outStr = '';
            $selStr = '';
        }

        return [
            'sql'    => $decStr . $insert . $outStr . $valStr . $selStr,
            'params' => $params,
        ];
    }

    /**
     * @param array $colVals
     * @throws \Exception if the column/values array does not have a valid structure
     */
    private static function validateColValsStructure(array $colVals)
    {
        if (empty($colVals[0]) || !is_array($colVals[0])) {
            throw new \Exception('A valid array of columns/values to insert must be specified');
        }
    }

    /**
     * Returns true if the array of values is for a bulk insert.
     * @param array $values
     * @return bool
     */
    public static function isBulk(array $values)
    {
        return !empty($values[0]) && is_array($values[0]);
    }
}
