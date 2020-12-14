<?php

declare(strict_types=1);

namespace PeachySQL;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the BulkInsertResult object
 */
class BulkInsertResultTest extends TestCase
{
    public function testCreateRetrieve(): void
    {
        $result = new BulkInsertResult([48, 49, 50], 6, 2);
        $this->assertSame([48, 49, 50], $result->getIds());
        $this->assertSame(6, $result->getAffected());
        $this->assertSame(2, $result->getQueryCount());
    }
}
