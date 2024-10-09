<?php

declare(strict_types=1);

namespace PeachySQL\Test\src;

/**
 * Default test config. Values can be overridden with a LocalConfig child class.
 */
class Config
{
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
            'CharacterSet' => 'UTF-8',
        ];
    }
}
