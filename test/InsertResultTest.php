<?php

declare(strict_types=1);

namespace PeachySQL\Test;

use PeachySQL\InsertResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the InsertResult object
 */
class InsertResultTest extends TestCase
{
    public function testCreateRetrieve(): void
    {
        $result = new InsertResult(24, 2);
        $this->assertSame(24, $result->getId());
        $this->assertSame(2, $result->getAffected());
    }

    public function testNoInsertId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Inserted row does not have an auto-incremented ID');
        $result = new InsertResult(0, 1);
        $result->getId();
    }
}
