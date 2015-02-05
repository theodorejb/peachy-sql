<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for select query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Select extends Query
{
    /**
     * Builds a select query using the specified table name, columns, and where clause array
     *
     * @param string   $tableName The name of the table to select from
     * @param string[] $columns   An array of columns to select from (all columns if empty)
     * @param string[] $validCols An array of valid columns (to prevent SQL injection)
     * @param array    $where     An array of columns/values to filter the select query
     * @param string[] $orderBy   One or more column names to sort by
     * @return array An array containing the SELECT query and bound parameters
     */
    public static function buildQuery($tableName, array $columns = [], array $validCols = [], array $where = [], array $orderBy = [])
    {
        self::validateTableName($tableName);
        $whereClause = self::buildWhereClause($where, $validCols);

        if (!empty($columns)) {
            self::validateColumns($columns, $validCols);
            $insertCols = implode(', ', $columns);
        } else {
            $insertCols = '*';
        }

        $sql = "SELECT $insertCols FROM $tableName" . $whereClause['sql'] . self::buildOrderByClause($orderBy, $validCols);
        return ['sql' => $sql, 'params' => $whereClause['params']];
    }

    /**
     * @param string[] $orderBy One or more column names to sort by
     * @param string[] $validCols
     * @return string
     */
    private static function buildOrderByClause(array $orderBy, array $validCols)
    {
        if (empty($orderBy)) {
            return '';
        }

        self::validateColumns($orderBy, $validCols);
        return ' ORDER BY ' . implode(', ', $orderBy);
    }
}
