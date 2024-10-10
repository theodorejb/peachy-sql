<?php

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
    public bool $fetchNextSyntax = true;
    public string $insertIdSelector = '; SELECT SCOPE_IDENTITY() AS RowID;';
}
