<?php

namespace PeachySQL\QueryBuilder;

/**
 * Tests for the Update query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class UpdateTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildQuery()
    {
        $set = [
            'username' => 'TestUser',
            'othercol' => null
        ];

        $where = ['id' => 21];
        $actual = Update::buildQuery('TestTable', $set, $where, ['id', 'username', 'othercol']);
        $expected = 'UPDATE TestTable SET username = ?, othercol = ? WHERE id = ?';

        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['TestUser', null, 21], $actual->getParams());
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildQueryInvalidColumns()
    {
        Update::buildQuery('TestTable', ['fizzbuzz' => null], ['bar' => 1], ['foo', 'bar']);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildQueryInvalidColumnsInWhere()
    {
        Update::buildQuery('TestTable', ['foo' => null], ['fizzbuzz' => 1], ['foo', 'bar']);
    }
}
