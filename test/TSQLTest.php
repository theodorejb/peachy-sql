<?php

namespace PeachySQL;

/**
 * Tests for the T-SQL PeachySQL implementation
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class TSQLTest extends \PHPUnit_Framework_TestCase
{
    public function columnValsProvider()
    {
        return [
            [
                ['col1', 'col2'],
                [['foo1', 'foo2'], ['bar1', 'bar2']]
            ]
        ];
    }

    /**
     * @dataProvider columnValsProvider
     */
    public function testBuildInsertQueryWithoutInsertId($columns, $values)
    {
        // a two-dimensional array should insert multiple rows
        $actual = TSQL::buildInsertQuery('TestTable', $columns, $columns, $values);
        $expected = 'INSERT INTO TestTable (col1, col2) VALUES (?,?), (?,?)';
        $this->assertSame($expected, $actual['sql']);
        $this->assertSame(['foo1', 'foo2', 'bar1', 'bar2'], $actual['params']);
    }

    /**
     * @dataProvider columnValsProvider
     */
    public function testBuildInsertQueryWithInsertId($columns, $values)
    {
        $actual = TSQL::buildInsertQuery('TestTable', $columns, $columns, $values, "pkColumn");
        $expected = 'DECLARE @ids TABLE(RowID int);'
        . ' INSERT INTO TestTable (col1, col2)'
        . ' OUTPUT inserted.pkColumn INTO @ids(RowID)'
        . ' VALUES (?,?), (?,?);'
        . ' SELECT * FROM @ids;';
        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame(['foo1', 'foo2', 'bar1', 'bar2'], $actual["params"]);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildInsertQueryInvalidColumns()
    {
        TSQL::buildInsertQuery("TestTable", ["foo", "fizzbuzz"], ["foo", "bar"], ["val1", "val2"]);
    }
}
