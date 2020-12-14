<?php

declare(strict_types=1);

namespace PeachySQL\SqlServer;

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
        } catch (\InvalidArgumentException $expected) {
            return;
        }

        $this->fail('escapeIdentifier failed to throw expected exception');
    }
}
