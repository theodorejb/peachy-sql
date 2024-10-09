<?php

namespace PeachySQL\Test\SqlServer;

use PeachySQL\SqlServer;
use PeachySQL\Test\DbTestCase;
use PeachySQL\Test\src\DbConnector;

class MssqlDbTest extends DbTestCase
{
    public static function tearDownAfterClass(): void
    {
        DbConnector::deleteMssqlTestTable();
    }

    public static function dbProvider(): array
    {
        return [
            [new SqlServer(DbConnector::getSqlsrvConn())],
        ];
    }
}
