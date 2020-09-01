<?php

declare(strict_types=1);

namespace PeachySQL\SqlServer;

use PeachySQL\BaseOptions;

/**
 * Handles options specific to SQL Server
 */
class Options extends BaseOptions
{
    /** @var int */
    protected $maxBoundParams = 2099;
    /** @var int */
    protected $maxInsertRows = 1000;
}
