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
     * @param string $tableName
     * @param array  $columns
     * @param array  $validColumns
     * @param array  $values
     * @param string $idCol
     * @return array
     * @throws \Exception if the columns or values array is empty
     */
    public static function buildQuery($tableName, array $columns, $validColumns, array $values, $idCol = null)
    {
        // make sure columns and values are specified
        if (empty($columns) || empty($values[0])) {
            throw new \Exception('Columns and values to insert must be specified');
        }

        self::validateTableName($tableName);
        self::validateColumns($columns, $validColumns);

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

        $insertCols = implode(', ', $columns);
        $insert = "INSERT INTO $tableName ($insertCols)";

        $bulkInsert = isset($values[0]) && is_array($values[0]);
        if (!$bulkInsert) {
            $values = [$values]; // make sure values is two-dimensional
        }

        $params = [];
        $valStr = ' VALUES';

        foreach ($values as $valArr) {
            $valStr .= ' (' . str_repeat('?,', count($valArr));
            $valStr = substr_replace($valStr, '),', -1); // replace trailing comma
            $params = array_merge($params, $valArr);
        }

        $valStr = substr_replace($valStr, '', -1); // remove trailing comma

        return [
            'sql'    => $decStr . $insert . $outStr . $valStr . $selStr,
            'params' => $params,
            'isBulk' => $bulkInsert,
        ];
    }
}
