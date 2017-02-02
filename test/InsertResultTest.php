<?php

namespace PeachySQL;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the InsertResult object
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class InsertResultTest extends TestCase
{
    public function testCreateRetrieve()
    {
        $result = new InsertResult(24, 2);
        $this->assertSame(24, $result->getId());
        $this->assertSame(2, $result->getAffected());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Inserted row does not have an auto-incremented ID
     */
    public function testNoInsertId()
    {
        $result = new InsertResult(0, 1);
        $result->getId();
    }
}
