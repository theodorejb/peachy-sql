<?php

declare(strict_types=1);

namespace PeachySQL\QueryBuilder;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the Update query builder
 */
class UpdateTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $set = [
            'username' => 'TestUser',
            'othercol' => null
        ];

        $where = ['id' => 21];

        $update = new Update(new \PeachySQL\SqlServer\Options());
        $actual = $update->buildQuery('TestTable', $set, $where);
        $expected = 'UPDATE TestTable SET "username" = ?, "othercol" = ? WHERE "id" = ?';

        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['TestUser', null, 21], $actual->getParams());
    }
}
