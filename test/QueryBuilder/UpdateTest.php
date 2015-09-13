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

        $options = new \PeachySQL\SqlServer\Options();
        $options->setTable('TestTable');
        $actual = (new Update($options))->buildQuery($set, $where);
        $expected = 'UPDATE TestTable SET [username] = ?, [othercol] = ? WHERE [id] = ?';

        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['TestUser', null, 21], $actual->getParams());
    }
}
