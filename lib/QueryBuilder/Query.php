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
}
