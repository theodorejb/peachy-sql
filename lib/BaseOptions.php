<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Base class for PeachySQL configuration state
 */
abstract class BaseOptions
{
    /**
     * The maximum number of parameters which can be bound in a single query.
     * If greater than zero, PeachySQL will batch insert queries to avoid the limit.
     */
    public int $maxBoundParams = 0;

    /**
     * The maximum number of rows which can be inserted via a single query.
     * If greater than zero, PeachySQL will batch insert queries to avoid the limit.
     */
    public int $maxInsertRows = 0;

    /**
     * Escapes a table or column name, and validates that it isn't blank
     */
    public function escapeIdentifier(string $identifier): string
    {
        if ($identifier === '') {
            throw new \InvalidArgumentException('Identifier cannot be blank');
        }

        // use standard double quotes to delimit identifiers
        $escaper = function (string $identifier): string {
            return '"' . str_replace('"', '""', $identifier) . '"';
        };

        $qualifiedIdentifiers = array_map($escaper, explode('.', $identifier));
        return implode('.', $qualifiedIdentifiers);
    }

    /**
     * Specify the maximum number of parameters which can be bound in a single query.
     * If greater than zero, PeachySQL will batch insert queries to avoid the limit.
     * @deprecated Use public property instead
     * @api
     */
    public function setMaxBoundParams(int $maxParams): void
    {
        if ($maxParams < 0) {
            throw new \InvalidArgumentException('The maximum number of bound parameters must be greater than or equal to zero');
        }

        $this->maxBoundParams = $maxParams;
    }

    /**
     * @deprecated Use public property instead
     * @api
     */
    public function getMaxBoundParams(): int
    {
        return $this->maxBoundParams;
    }

    /**
     * Specify the maximum number of rows which can be inserted via a single query.
     * If greater than zero, PeachySQL will batch insert queries to remove the limit.
     * @deprecated Use public property instead
     * @api
     */
    public function setMaxInsertRows(int $maxRows): void
    {
        if ($maxRows < 0) {
            throw new \InvalidArgumentException('The maximum number of insert rows must be greater than or equal to zero');
        }

        $this->maxInsertRows = $maxRows;
    }

    /**
     * @deprecated Use public property instead
     * @api
     */
    public function getMaxInsertRows(): int
    {
        return $this->maxInsertRows;
    }
}
