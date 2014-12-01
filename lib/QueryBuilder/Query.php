<?php

namespace PeachySQL\QueryBuilder;

/**
 * Base class used for query generation and validation
 */
abstract class Query
{
    /**
     * Throws an exception if a column does not exist in the array of valid columns
     * @param string[] $columns
     * @param string[] $validColumns
     * @throws \UnexpectedValueException
     */
    public static function validateColumns(array $columns, array $validColumns)
    {
        foreach ($columns as $col) {
            if (!in_array($col, $validColumns, true)) {
                throw new \UnexpectedValueException("Invalid column '$col'");
            }
        }
    }

    /**
     * Throws an exception if the specified table name is null or blank
     * @param string $name
     * @throws \Exception
     */
    public static function validateTableName($name)
    {
        if ($name === null || $name === "") {
            throw new \Exception('A valid table name must be set to generate queries');
        }
    }

    /**
     * @param array    $columnVals An associative array of columns and values to filter rows.
     *                             E.g. ["id" => 3] to only return rows where id is equal to 3.
     *                             If the value is an array, an IN(...) clause will be used.
     * @param string[] $validCols An array of valid columns for the table
     * @return array An array containing the SQL WHERE clause and bound parameters
     */
    protected static function buildWhereClause(array $columnVals, array $validCols)
    {
        $sql = '';
        $params = [];

        if (!empty($columnVals)) {
            self::validateColumns(array_keys($columnVals), $validCols);
            $sql .= ' WHERE';

            foreach ($columnVals as $column => $value) {
                if ($value === null) {
                    $comparison = 'IS NULL';
                } elseif (is_array($value) && !empty($value)) {
                    $comparison = 'IN('; // use IN(...) syntax

                    foreach ($value as $val) {
                        $comparison .= '?,';
                        $params[] = $val;
                    }

                    $comparison = substr_replace($comparison, ')', -1); // replace trailing comma
                } else {
                    $comparison = '= ?';
                    $params[] = $value;
                }

                $sql .= " $column $comparison AND";
            }

            $sql = substr_replace($sql, '', -4); // remove the trailing AND
        }

        return ['sql' => $sql, 'params' => $params];
    }
}
