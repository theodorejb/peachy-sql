<?php

declare(strict_types=1);

namespace PeachySQL\Test\SqlServer;

use PeachySQL\SqlServer;
use PeachySQL\Test\DbTestCase;
use PeachySQL\Test\src\App;

/**
 * @group mssql
 */
class MssqlDbTest extends DbTestCase
{
    private static ?SqlServer $db = null;

    protected function getExpectedBadSyntaxCode(): int
    {
        return 102;
    }

    protected function getExpectedBadSyntaxError(): string
    {
        return 'Incorrect syntax';
    }

    public static function dbProvider(): SqlServer
    {
        if (!self::$db) {
            $c = App::$config;
            $server = $c->getSqlsrvServer();
            $connInfo = $c->getSqlsrvConnInfo();
            $connStr = getenv('MSSQL_CONNECTION_STRING');

            if ($connStr !== false) {
                // running tests with GitHub Actions
                $server = getenv('SQLCMDSERVER');
                if ($server === false) {
                    throw new \Exception('SQLCMDSERVER not set');
                }
                $connInfo['UID'] = getenv('SQLCMDUSER');
                $connInfo['PWD'] = getenv('SQLCMDPASSWORD');
            }

            $connection = sqlsrv_connect($server, $connInfo);

            if (!$connection) {
                throw new \Exception('Failed to connect to SQL server: ' . print_r(sqlsrv_errors(), true));
            }

            self::$db = new SqlServer($connection);
            self::createTestTable(self::$db);
        }

        return self::$db;
    }

    private static function createTestTable(SqlServer $db): void
    {
        $sql = "
            DROP TABLE IF EXISTS Users;
            CREATE TABLE Users (
                user_id INT PRIMARY KEY IDENTITY(1,1) NOT NULL,
                name NVARCHAR(50) NOT NULL,
                dob DATE NOT NULL,
                weight FLOAT NOT NULL,
                is_disabled BIT NOT NULL,
                uuid BINARY(16) NULL
            )";

        $db->query($sql);
    }
}
