<?php

declare(strict_types=1);

namespace PeachySQL\Test;

/**
 * Default test config. Values can be overridden with a LocalConfig child class.
 */
class Config
{
    public function testMysql(): bool
    {
        return true;
    }

    public function testSqlsrv(): bool
    {
        return false; // don't test by default since it isn't configured on CI
    }

    public function getMysqlHost(): string
    {
        return '127.0.0.1';
    }

    public function getMysqlUser(): string
    {
        return 'root';
    }

    public function getMysqlPassword(): string
    {
        return '';
    }

    public function getMysqlDatabase(): string
    {
        return 'PeachySQL';
    }

    public function getSqlsrvServer(): string
    {
        return '(local)\SQLEXPRESS';
    }

    public function getSqlsrvConnInfo(): array
    {
        return [
            'Database' => 'PeachySQL',
            'ReturnDatesAsStrings' => true,
        ];
    }
}
