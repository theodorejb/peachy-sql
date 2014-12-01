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
        $columns = ['col1', 'col2', 'col3'];
        $values = [['val1', 'val2', 'val3']];

        $actual = Insert::buildQuery('TestTable', $columns, $columns, $values);
        $expected = 'INSERT INTO TestTable (col1, col2, col3) VALUES (?,?,?)';
        $this->assertSame($expected, $actual['sql']);
        $this->assertSame(['val1', 'val2', 'val3'], $actual['params']);
    }

    /**
     * Tests building an insert query with OUTPUT clause for SQL Server
     */
    public function testBuildQueryWithInsertId()
    {
        $columns = ['col1', 'col2'];
        $values = [['foo1', 'foo2'], ['bar1', 'bar2']];

        $actual = Insert::buildQuery('TestTable', $columns, $columns, $values, 'pkColumn');
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
        Insert::buildQuery("TestTable", ["fizzbuzz", "foo"], ["foo", "bar"], ["val1", "val2"]);
    }
}
