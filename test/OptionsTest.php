<?php

namespace PeachySQL;

use PeachySQL\Mysql\Options;

/**
 * Tests for PeachySQL configuration options
 */
class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testTable()
    {
        $options = new Options();
        $options->setTable('MyTable');
        $this->assertSame('MyTable', $options->getTable());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testColumns()
    {
        $options = new Options();
        $validCols = ['col1', 'col2'];
        $options->setColumns($validCols);
        $this->assertSame($validCols, $options->getColumns());
        $options->setColumns(['col1', true]); // should throw exception
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxBoundParams()
    {
        $options = new Options();
        $options->setMaxBoundParams(2000);
        $this->assertSame(2000, $options->getMaxBoundParams());
        $options->setMaxBoundParams(-1); // should throw exception
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxInsertRows()
    {
        $options = new Options();
        $options->setMaxInsertRows(1000);
        $this->assertSame(1000, $options->getMaxInsertRows());
        $options->setMaxInsertRows(-1); // should throw exception
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAutoIncrementValue()
    {
        $options = new Options();
        $options->setAutoIncrementValue(2);
        $this->assertSame(2, $options->getAutoIncrementValue());
        $options->setAutoIncrementValue(0); // should throw exception
    }

    public function testIdColumn()
    {
        $options = new SqlServer\Options();
        $options->setIdColumn('MyColumn');
        $this->assertSame('MyColumn', $options->getIdColumn());
    }
}
