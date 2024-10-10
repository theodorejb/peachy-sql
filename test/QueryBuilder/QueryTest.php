<?php

declare(strict_types=1);

namespace PeachySQL\Test\QueryBuilder;

use PeachySQL\Mysql\Options;
use PeachySQL\QueryBuilder\Query;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the base query builder
 */
class QueryTest extends TestCase
{
    public function testEscapeIdentifier(): void
    {
        $options = new \PeachySQL\SqlServer\Options();
        $query = new Query($options);
        $actual = $query->escapeIdentifier('Test"Identifier');
        $this->assertSame('"Test""Identifier"', $actual);

        try {
            $query->escapeIdentifier(''); // should throw exception
            $this->fail('escapeIdentifier failed to throw expected exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Identifier cannot be blank', $e->getMessage());
        }

        $query = new Query(new Options()); // test syntax for MySQL without ANSI_QUOTES enabled
        $actual = $query->escapeIdentifier('My`Identifier');
        $this->assertSame('`My``Identifier`', $actual);
    }
}
