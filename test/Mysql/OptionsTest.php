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
    public function testMaxBoundParams(): void
    {
        $options = new Options();
        $options->setMaxBoundParams(2000);
        $this->assertSame(2000, $options->getMaxBoundParams());

        try {
            $options->setMaxBoundParams(-1); // should throw exception
        } catch (\InvalidArgumentException $expected) {
            return;
        }

        $this->fail('setMaxBoundParams failed to throw expected exception');
    }

    public function testMaxInsertRows(): void
    {
        $options = new Options();
        $options->setMaxInsertRows(1000);
        $this->assertSame(1000, $options->getMaxInsertRows());

        try {
            $options->setMaxInsertRows(-1); // should throw exception
        } catch (\InvalidArgumentException $expected) {
            return;
        }

        $this->fail('setMaxInsertRows failed to throw expected exception');
    }

    public function testEscapeIdentifier(): void
    {
        $options = new Options();
        $actual = $options->escapeIdentifier('My`Identifier');
        $this->assertSame('`My``Identifier`', $actual);

        try {
            $options->escapeIdentifier(''); // should throw exception
        } catch (\InvalidArgumentException $expected) {
            return;
        }

        $this->fail('escapeIdentifier failed to throw expected exception');
    }
}
