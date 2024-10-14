<?php

declare(strict_types=1);

namespace PeachySQL\Test\Pgsql;

use PeachySQL\Pgsql;
use PeachySQL\Test\DbTestCase;
use PeachySQL\Test\src\App;
use PDO;

/**
 * @group pgsql
 */
class PgsqlDbTest extends DbTestCase
{
    private static ?Pgsql $db = null;

    protected function getExpectedBadSyntaxCode(): int
    {
        return 7;
    }

    public static function dbProvider(): Pgsql
    {
        if (!self::$db) {
            $c = App::$config;
            $pdo = new PDO($c->getPgsqlDsn(), $c->getPgsqlUser(), $c->getPgsqlPassword(), [
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            self::$db = new Pgsql($pdo);
            self::createTestTable(self::$db);
        }

        return self::$db;
    }

    private static function createTestTable(Pgsql $db): void
    {
        $sql = "
            CREATE TABLE Users (
                user_id SERIAL PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                dob DATE NOT NULL,
                weight REAL NOT NULL,
                is_disabled BOOLEAN NOT NULL,
                uuid bytea NULL
            )";

        $db->query("DROP TABLE IF EXISTS Users");
        $db->query($sql);
    }
}
