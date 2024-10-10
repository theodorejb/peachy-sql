<?php

declare(strict_types=1);

namespace PeachySQL\Test\Mysql;

use PeachySQL\Mysql\Options;
use PHPUnit\Framework\TestCase;

/**
 * Tests base as well as MySQL-specific configuration settings
 */
class OptionsTest extends TestCase
{
    public function testEscapeIdentifier(): void
    {
        $options = new Options();
        $actual = $options->escapeIdentifier('My`Identifier');
        $this->assertSame('`My``Identifier`', $actual);

        try {
            $options->escapeIdentifier(''); // should throw exception
            $this->fail('escapeIdentifier failed to throw expected exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Identifier cannot be blank', $e->getMessage());
        }
    }
}
