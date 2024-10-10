<?php

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

    public bool $fetchNextSyntax = false;
    public string $insertIdSelector = '';

    /**
     * The character used to quote identifiers.
     */
    public string $identifierQuote = '"';
}
