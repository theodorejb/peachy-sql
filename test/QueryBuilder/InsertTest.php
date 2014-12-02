<?php

namespace PeachySQL\QueryBuilder;

/**
 * Tests for the Insert query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class InsertTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildQuery()
    {
        $colVals = [
            'col1' => 'val1',
            'col2' => 'val2',
            'col3' => 'val3',
        ];

        $actual = Insert::buildQuery('TestTable', [$colVals], array_keys($colVals));
        $expected = 'INSERT INTO TestTable (col1, col2, col3) VALUES (?,?,?)';
        $this->assertSame($expected, $actual['sql']);
        $this->assertSame(['val1', 'val2', 'val3'], $actual['params']);
    }

    /**
     * Tests building an insert query with OUTPUT clause for SQL Server
     */
    public function testBuildQueryWithIdColumn()
    {
        $colVals = [
            [
                'col1' => 'foo1',
                'col2' => 'foo2',
            ],
            [
                'col1' => 'bar1',
                'col2' => 'bar2',
            ],
        ];

        $actual = Insert::buildQuery('TestTable', $colVals, array_keys($colVals[0]), 'pkColumn');
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
    public function testBuildQueryInvalidColumns()
    {
        $colVals = [['fizzbuzz' => 'foo', 'foo' => 'bar']];
        Insert::buildQuery('TestTable', $colVals, ['val1', 'val2']);
    }
}
