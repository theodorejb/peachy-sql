<?php

// Any of these settings can be overridden by creating a "config.user.php" file in
// this directory which returns a subset of or replacement for the following array.

return [
    'testWith' => [
        'mysql'  => true,
        'sqlsrv' => false, // don't test by default since it isn't configured on CI
    ],
    'db' => [
        'mysql' => [
            'host'     => '127.0.0.1',
            'username' => 'root',
            'password' => '',
            'database' => 'PeachySQL',
        ],
        'sqlsrv' => [
            'serverName'     => '(local)\SQLEXPRESS',
            'connectionInfo' => [
                'Database'             => 'PeachySQL',
                'ReturnDatesAsStrings' => true,
            ],
        ],
    ],
];
