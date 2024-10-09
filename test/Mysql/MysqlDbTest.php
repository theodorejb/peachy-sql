<?php

namespace PeachySQL\Test\Mysql;

use PeachySQL\Mysql;
use PeachySQL\Test\DbTestCase;
use PeachySQL\Test\src\DbConnector;

class MysqlDbTest extends DbTestCase
{
    public static function tearDownAfterClass(): void
    {
        DbConnector::deleteMysqlTestTable();
    }

    public static function dbProvider(): array
    {
        return [
            [new Mysql(DbConnector::getMysqlConn())],
        ];
    }
}
