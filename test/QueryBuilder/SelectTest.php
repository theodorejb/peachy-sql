<?php

namespace PeachySQL\QueryBuilder;

/**
 * Tests for the Select query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class SelectTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildQueryAllRows()
    {
        $actual = Select::buildQuery('TestTable');
        $expected = 'SELECT * FROM TestTable';
        $this->assertSame($expected, $actual['sql']);
    }

    public function testBuildQueryWhere()
    {
        $cols = ['username', 'password'];

        $where = [
            'username' => 'TestUser',
            'password' => 'TestPassword',
            'othercol' => null
        ];

        $validCols = array_keys($where);

        $actual = Select::buildQuery('TestTable', $cols, $validCols, $where);
        $expected = 'SELECT username, password FROM TestTable WHERE '
            . 'username = ? AND password = ? AND othercol IS NULL';
        $this->assertSame($expected, $actual['sql']);
        $this->assertSame(['TestUser', 'TestPassword'], $actual['params']);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildQueryInvalidColumns()
    {
        Select::buildQuery('TestTable', ['fizzbuzz'], ['foo', 'bar']);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildQueryInvalidColumnsInWhere()
    {
        Select::buildQuery('TestTable', ['foo'], ['foo', 'bar'], ['fizzbuzz' => null]);
    }
}
