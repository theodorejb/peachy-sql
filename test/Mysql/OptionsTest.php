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
            $this->fail('setMaxBoundParams failed to throw expected exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('The maximum number of bound parameters must be greater than or equal to zero', $e->getMessage());
        }
    }

    public function testMaxInsertRows(): void
    {
        $options = new Options();
        $options->setMaxInsertRows(1000);
        $this->assertSame(1000, $options->getMaxInsertRows());

        try {
            $options->setMaxInsertRows(-1); // should throw exception
            $this->fail('setMaxInsertRows failed to throw expected exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('The maximum number of insert rows must be greater than or equal to zero', $e->getMessage());
        }
    }

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
