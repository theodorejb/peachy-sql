<?php

require 'vendor/autoload.php';
require 'test/db/TestDbConnector.php'; // not autoloaded by Composer

$config = require 'test/config.php';

if (is_readable('test/config.user.php')) {
    $userConfig = require 'test/config.user.php';
    $config = array_replace_recursive($config, $userConfig);
}

PeachySQL\TestDbConnector::setConfig($config);
