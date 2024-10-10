<?php

declare(strict_types=1);

namespace PeachySQL\Test\Mysql;

use PeachySQL\Mysql;
use PeachySQL\Test\DbTestCase;
use PeachySQL\Test\src\App;

/**
 * @group mysql
 */
class MysqlDbTest extends DbTestCase
{
    private static ?Mysql $db = null;

    protected function getExpectedBadSyntaxCode(): int
    {
        return 1064;
    }

    protected function getExpectedBadSyntaxError(): string
    {
        return 'error in your SQL syntax';
    }

    public static function dbProvider(): Mysql
    {
        if (!self::$db) {
            $c = App::$config;
            $port = getenv('DB_PORT');

            if ($port === false) {
                $port = 3306;
            } else {
                $port = (int) $port;
            }

            $mysqli = new \mysqli($c->getMysqlHost(), $c->getMysqlUser(), $c->getMysqlPassword(), $c->getMysqlDatabase(), $port);

            if ($mysqli->connect_error !== null) {
                throw new \Exception('Failed to connect to MySQL: ' . $mysqli->connect_error);
            }

            self::$db = new Mysql($mysqli);
            self::createTestTable(self::$db);
        }

        return self::$db;
    }

    private static function createTestTable(Mysql $db): void
    {
        $sql = "
            CREATE TABLE Users (
                user_id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                name VARCHAR(50) NOT NULL,
                dob DATE NOT NULL,
                weight FLOAT NOT NULL,
                isDisabled BOOLEAN NOT NULL,
                uuid BINARY(16) NULL
            )";

        $db->query("DROP TABLE IF EXISTS Users");
        $db->query($sql);
    }
}
