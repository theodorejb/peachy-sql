<?php

namespace PeachySQL\Mysql;

/**
 * Tests base as well as MySQL-specific configuration settings
 */
class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testMaxBoundParams()
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

    public function testMaxInsertRows()
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

    public function testEscapeIdentifier()
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
