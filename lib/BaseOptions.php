<?php

namespace PeachySQL;

/**
 * Base class for PeachySQL configuration state
 */
abstract class BaseOptions
{
    private $table = '';
    protected $maxBoundParams = 0;
    protected $maxInsertRows = 0;

    /**
     * Escapes a table or column name, and validates that it isn't blank
     * @param string $identifier
     * @return string
     */
    public function escapeIdentifier($identifier)
    {
        if (gettype($identifier) !== 'string') {
            throw new \InvalidArgumentException('Identifier must be a string');
        } elseif ($identifier === '') {
            throw new \InvalidArgumentException('Identifier cannot be blank');
        }

        // use standard double quotes to delimit identifiers
        $escaper = function ($identifier) { return '"' . str_replace('"', '""', $identifier) . '"'; };
        $qualifiedIdentifiers = array_map($escaper, explode('.', $identifier));
        return implode('.', $qualifiedIdentifiers);
    }

    /**
     * Specify the table to select, insert, update, and delete from.
     * Table names are not automatically escaped since this would prevent
     * using table names that contain a period.
     * @param string $table
     * @deprecated Pass a table name to the shorthand methods instead
     */
    public function setTable($table)
    {
        if (gettype($table) !== 'string') {
            throw new \InvalidArgumentException('Table name must be a string');
        }

        $this->table = $table;
    }

    /**
     * @return string
     * @deprecated This method isn't useful if no table is set
     */
    public function getTable()
    {
        if ($this->table === '') {
            throw new \RuntimeException('Table has not been set');
        }

        return $this->table;
    }

    /**
     * Specify the maximum number of parameters which can be bound in a single query.
     * If greater than zero, PeachySQL will batch insert queries to avoid the limit.
     * @param int $maxParams
     */
    public function setMaxBoundParams($maxParams)
    {
        if (gettype($maxParams) !== 'integer' || $maxParams < 0) {
            throw new \InvalidArgumentException('The maximum number of bound '
                . 'parameters must be an integer greater than or equal to zero');
        }

        $this->maxBoundParams = $maxParams;
    }

    /**
     * @return int
     */
    public function getMaxBoundParams()
    {
        return $this->maxBoundParams;
    }

    /**
     * Specify the maximum number of rows which can be inserted via a single query.
     * If greater than zero, PeachySQL will batch insert queries to remove the limit.
     * @param int $maxRows
     */
    public function setMaxInsertRows($maxRows)
    {
        if (gettype($maxRows) !== 'integer' || $maxRows < 0) {
            throw new \InvalidArgumentException('The maximum number of insert '
                . 'rows must be an integer greater than or equal to zero');
        }

        $this->maxInsertRows = $maxRows;
    }

    /**
     * @return int
     */
    public function getMaxInsertRows()
    {
        return $this->maxInsertRows;
    }
}
