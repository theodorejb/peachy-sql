<?php

namespace PeachySQL\SqlServer;

/**
 * Tests SQL Server options
 */
class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testTable()
    {
        $options = new Options();
        $options->setTable('MyTable');
        $this->assertSame('MyTable', $options->getTable());
    }

    public function testEscapeIdentifier()
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

    public function testIdColumn()
    {
        $options = new Options();
        $options->setIdColumn('MyColumn');
        $this->assertSame('"MyColumn"', $options->getIdColumn());
    }
}
