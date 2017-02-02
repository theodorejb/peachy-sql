<?php

namespace PeachySQL\QueryBuilder;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the Delete query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class DeleteTest extends TestCase
{
    public function testBuildQuery()
    {
        $delete = new Delete(new \PeachySQL\SqlServer\Options());
        $where = ['id' => 5, 'username' => ['tester', 'tester2']];
        $actual = $delete->buildQuery('TestTable', $where);
        $expected = 'DELETE FROM TestTable WHERE "id" = ? AND "username" IN(?,?)';

        $this->assertSame($expected, $actual->getSql());
        $this->assertSame([5, 'tester', 'tester2'], $actual->getParams());
    }
}
