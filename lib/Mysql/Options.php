<?php

declare(strict_types=1);

namespace PeachySQL\Mysql;

use PeachySQL\BaseOptions;

/**
 * Handles MySQL-specific options
 */
class Options extends BaseOptions
{
    protected int $maxBoundParams = 65536; // 2^16

    // use backticks to delimit identifiers since not everyone uses ANSI mode
    public function escapeIdentifier(string $identifier): string
    {
        if ($identifier === '') {
            throw new \InvalidArgumentException('Identifier cannot be blank');
        }

        $escaper = function (string $identifier): string {
            return '`' . str_replace('`', '``', $identifier) . '`';
        };

        $qualifiedIdentifiers = array_map($escaper, explode('.', $identifier));
        return implode('.', $qualifiedIdentifiers);
    }
}
