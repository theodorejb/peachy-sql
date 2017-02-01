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
     * @deprecated Isn't needed since new insert methods use SCOPE_IDENTITY() to retrieve IDs
     */
    public function setIdColumn($column)
    {
        // allow ID column to be unset with a blank string
        if ($column === '') {
            $this->identityColumn = $column;
        } else {
            $this->identityColumn = $this->escapeIdentifier($column);
        }
    }

    /**
     * Returns the table's identity column
     * @return string
     * @deprecated Isn't useful if no ID column is set
     */
    public function getIdColumn()
    {
        return $this->identityColumn;
    }
}
