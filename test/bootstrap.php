<?php

declare(strict_types=1);

use PeachySQL\Test\DbConnector;

require 'vendor/autoload.php';

$config = require 'test/config.php';

if (is_readable('test/config.user.php')) {
    /** @psalm-suppress MissingFile, MixedAssignment */
    $userConfig = require 'test/config.user.php';
    /** @psalm-suppress MixedArgument */
    $config = array_replace_recursive($config, $userConfig);
}

/** @psalm-suppress MixedArgumentTypeCoercion */
DbConnector::setConfig($config);
