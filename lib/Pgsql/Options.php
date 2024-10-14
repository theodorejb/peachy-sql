<?php

declare(strict_types=1);

namespace PeachySQL\Pgsql;

use PeachySQL\BaseOptions;

/**
 * Handles PostgreSQL-specific options
 */
class Options extends BaseOptions
{
    // https://stackoverflow.com/questions/6581573/what-are-the-max-number-of-allowable-parameters-per-database-provider-type
    public int $maxBoundParams = 65_535; // 2 ** 16 - 1;
}
