<?php

declare(strict_types=1);

namespace PeachySQL\Test\src;

/**
 * Default test config. Values can be overridden with a LocalConfig child class.
 */
class Config
{
    public function getMysqlDsn(): string
    {
        return "mysql:host=127.0.0.1;port=3306;dbname=PeachySQL";
    }

    public function getMysqlUser(): string
    {
        return 'root';
    }

    public function getMysqlPassword(): string
    {
        return '';
    }

    public function getPgsqlDsn(string $database): string
    {
        return "pgsql:host=localhost;dbname=$database";
    }

    public function getPgsqlUser(): string
    {
        return 'postgres';
    }

    public function getPgsqlPassword(): string
    {
        return 'postgres';
    }

    public function getSqlsrvServer(): string
    {
        return '(local)\SQLEXPRESS';
    }
}
