<?php

namespace PeachySQL;

/**
 * Base class for PeachySQL configuration state
 */
abstract class BaseOptions
{
    private $table = '';
    private $columns = [];
    protected $maxBoundParams = 0;
    protected $maxInsertRows = 0;

    /**
     * Specify the table to select, insert, update, and delete from
     * @param string $table
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
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string[] $columns
     */
    public function setColumns(array $columns)
    {
        foreach ($columns as $column) {
            if (gettype($column) !== 'string') {
                throw new \InvalidArgumentException('All column names must be strings');
            }
        }

        $this->columns = $columns;
    }

    /**
     * @return string[]
     */
    public function getColumns()
    {
        return $this->columns;
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
