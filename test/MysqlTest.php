<?php

namespace PeachySQL;

/**
 * Tests for the MySQL PeachySQL implementation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class MysqlTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildInsertQuery()
    {
        $columns = ['col1', 'col2', 'col3'];
        $values = [['val1', 'val2', 'val3']];

        $actual = Mysql::buildInsertQuery('TestTable', $columns, $columns, $values);
        $expected = 'INSERT INTO TestTable (col1, col2, col3) VALUES (?,?,?)';
        $this->assertSame($expected, $actual['sql']);
        $this->assertSame(['val1', 'val2', 'val3'], $actual['params']);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildInsertQueryInvalidColumns()
    {
        Mysql::buildInsertQuery("TestTable", ["fizzbuzz", "foo"], ["foo", "bar"], ["val1", "val2"]);
    }
}
