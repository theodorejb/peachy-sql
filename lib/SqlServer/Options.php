<?php

namespace PeachySQL\SqlServer;

use PeachySQL\BaseOptions;

/**
 * Handles options specific to SQL Server
 */
class Options extends BaseOptions
{
    protected $maxBoundParams = 2099;
    protected $maxInsertRows = 1000;
    private $identityColumn = '';

    /**
     * Specify the table's identity column (used to retrieve insert IDs)
     * @param string $column
     */
    public function setIdColumn($column)
    {
        if (gettype($column) !== 'string') {
            throw new \InvalidArgumentException('Identity column name must be a string');
        }

        $this->identityColumn = $column;
    }

    /**
     * Returns the table's identity column
     * @return string
     */
    public function getIdColumn()
    {
        return $this->identityColumn;
    }
}
