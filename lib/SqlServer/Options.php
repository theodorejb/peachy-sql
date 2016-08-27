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
}
