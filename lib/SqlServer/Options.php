<?php

declare(strict_types=1);

namespace PeachySQL\SqlServer;

use PeachySQL\BaseOptions;

/**
 * Handles options specific to SQL Server
 */
class Options extends BaseOptions
{
    protected int $maxBoundParams = 2099;
    protected int $maxInsertRows = 1000;
}
