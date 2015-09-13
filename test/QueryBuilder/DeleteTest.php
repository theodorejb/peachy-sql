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
        $options = new \PeachySQL\Mysql\Options();
        $options->setTable('TestTable');

        $where = ['id' => 5, 'username' => ['tester', 'tester2']];
        $actual = (new Delete($options))->buildQuery($where);
        $expected = 'DELETE FROM TestTable WHERE `id` = ? AND `username` IN(?,?)';

        $this->assertSame($expected, $actual->getSql());
        $this->assertSame([5, 'tester', 'tester2'], $actual->getParams());
    }
}
