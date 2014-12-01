<?php

namespace PeachySQL\QueryBuilder;

/**
 * Class used for update query generation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class Update extends Query
{
    /**
     * @param string   $tableName The name of the table to update
     * @param array    $set       An array of columns/values to update
     * @param array    $where     An array of columns/values to restrict the update to
     * @param string[] $validCols An array of valid columns
     * @return array An array containing the SQL string and bound parameters
     * @throws \Exception if the $set or $where arrays are empty
     */
    public static function buildQuery($tableName, array $set, array $where, array $validCols)
    {
        if (empty($set) || empty($where)) {
            throw new \Exception('Set and where arrays cannot be empty');
        }

        self::validateTableName($tableName);
        self::validateColumns(array_keys($set), $validCols);

        $params = [];
        $sql = "UPDATE $tableName SET ";

        foreach ($set as $column => $value) {
            $sql .= "$column = ?, ";
            $params[] = $value;
        }

        $sql = substr_replace($sql, '', -2); // remove trailing comma
        $whereClause = self::buildWhereClause($where, $validCols);
        $sql .= $whereClause['sql'];
        $allParams = array_merge($params, $whereClause['params']);

        return ['sql' => $sql, 'params' => $allParams];
    }
}
