<?php

declare(strict_types=1);

namespace PeachySQL\Test\SqlServer;

use PeachySQL\SqlServer\Options;
use PHPUnit\Framework\TestCase;

/**
 * Tests SQL Server options
 */
class OptionsTest extends TestCase
{
    public function testEscapeIdentifier(): void
    {
        $options = new Options();
        $actual = $options->escapeIdentifier('Test"Identifier');
        $this->assertSame('"Test""Identifier"', $actual);

        try {
            $options->escapeIdentifier(''); // should throw exception
            $this->fail('escapeIdentifier failed to throw expected exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Identifier cannot be blank', $e->getMessage());
        }
    }
}
