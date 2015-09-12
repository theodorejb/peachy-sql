<?php

namespace PeachySQL\QueryBuilder;

/**
 * Tests for the Delete query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class DeleteTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildQuery()
    {
        $where = ['id' => 5, 'username' => ['tester', 'tester2']];
        $actual = Delete::buildQuery('TestTable', $where, ['id', 'username']);
        $expected = 'DELETE FROM TestTable WHERE id = ? AND username IN(?,?)';

        $this->assertSame($expected, $actual->getSql());
        $this->assertSame([5, 'tester', 'tester2'], $actual->getParams());
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildQueryInvalidColumnsInWhere()
    {
        Delete::buildQuery('TestTable', ['fizzbuzz' => null], ['foo', 'bar']);
    }
}
