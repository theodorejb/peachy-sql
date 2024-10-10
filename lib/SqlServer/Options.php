<?php

declare(strict_types=1);

namespace PeachySQL\SqlServer;

use PeachySQL\BaseOptions;

/**
 * Handles options specific to SQL Server
 */
class Options extends BaseOptions
{
    // https://learn.microsoft.com/en-us/sql/sql-server/maximum-capacity-specifications-for-sql-server
    public int $maxBoundParams = 2100 - 1;
    public int $maxInsertRows = 1000;
}
