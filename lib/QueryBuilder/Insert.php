<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for insert query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Insert extends Query
{
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
