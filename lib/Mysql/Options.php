<?php

namespace PeachySQL\Mysql;

use PeachySQL\BaseOptions;

/**
 * Handles MySQL-specific options
 */
class Options extends BaseOptions
{
    // https://stackoverflow.com/questions/6581573/what-are-the-max-number-of-allowable-parameters-per-database-provider-type
    public int $maxBoundParams = 65_535; // 2 ** 16 - 1;

    // needed since not everyone uses ANSI mode
    public string $identifierQuote = '`';
}
