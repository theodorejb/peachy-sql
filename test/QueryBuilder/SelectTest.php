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
        $this->assertSame($expected, $actual->getSql());
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
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['TestUser', 'TestPassword'], $actual->getParams());
    }

    public function testBuildQueryOrderBy()
    {
        $cols = ['username', 'firstname', 'lastname'];
        $validCols = $cols;
        $where = [];
        $orderBy = ['lastname', 'firstname'];

        $actual = Select::buildQuery('TestTable', $cols, $validCols, $where, $orderBy);
        $expected = 'SELECT username, firstname, lastname FROM TestTable ORDER BY lastname, firstname';
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame([], $actual->getParams());
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

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildQueryInvalidColumnsInOrderBy()
    {
        Select::buildQuery('TestTable', ['foo'], ['foo'], ['foo' => 1], ['bar']);
    }
}
