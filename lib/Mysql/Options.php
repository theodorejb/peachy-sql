<?php

namespace PeachySQL\Mysql;

use PeachySQL\BaseOptions;

/**
 * Handles MySQL-specific options
 */
class Options extends BaseOptions
{
    protected $maxBoundParams = 65536; // 2^16
    private $autoIncrementVal = 1;

    // use backticks to delimit identifiers since not everyone uses ANSI mode
    public function escapeIdentifier($identifier)
    {
        if (gettype($identifier) !== 'string') {
            throw new \InvalidArgumentException('Identifier must be a string');
        } elseif ($identifier === '') {
            throw new \InvalidArgumentException('Identifier cannot be blank');
        }

        $escaper = function ($identifier) { return '`' . str_replace('`', '``', $identifier) . '`'; };
        $qualifiedIdentifiers = array_map($escaper, explode('.', $identifier));
        return implode('.', $qualifiedIdentifiers);
    }

    /**
     * Specify the interval between successive auto-incremented IDs in
     * the table (used to retrieve array of insert IDs for bulk inserts).
     * @param int $increment
     * @deprecated Pass the identity increment value to the insert method instead
     */
    public function setAutoIncrementValue($increment)
    {
        if (gettype($increment) !== 'integer' || $increment < 1) {
            throw new \InvalidArgumentException('Auto increment value must be an integer greater than zero');
        }

        $this->autoIncrementVal = $increment;
    }

    /**
     * @return int
     * @deprecated This method isn't useful if no increment value is set
     */
    public function getAutoIncrementValue()
    {
        return $this->autoIncrementVal;
    }
}
