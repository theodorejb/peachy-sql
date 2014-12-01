<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for select query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Select extends Query
{
    /**
     * Builds a select query using the specified table name, columns, and where clause array.
     * @param string   $tableName The name of the table to query
     * @param string[] $columns   An array of columns to select from (all columns if empty)
     * @param string[] $validCols An array of valid columns (to prevent SQL injection)
     * @param array    $where     An array of columns/values to filter the select query
     * @return array An array containing the SELECT query and bound parameters
     */
    public static function buildQuery($tableName, array $columns = [], array $validCols = [], array $where = [])
    {
        self::validateTableName($tableName);
        $whereClause = self::buildWhereClause($where, $validCols);

        if (!empty($columns)) {
            self::validateColumns($columns, $validCols);
            $insertCols = implode(', ', $columns);
        } else {
            $insertCols = '*';
        }

        $sql = "SELECT $insertCols FROM $tableName" . $whereClause['sql'];
        return ['sql' => $sql, 'params' => $whereClause['params']];
    }
}
