<?php

namespace PeachySQL;

/**
 * Tests for the BulkInsertResult object
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class BulkInsertResultTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRetrieve()
    {
        $result = new BulkInsertResult([48, 49, 50], 6, 2);
        $this->assertSame([48, 49, 50], $result->getIds());
        $this->assertSame(6, $result->getAffected());
        $this->assertSame(2, $result->getQueryCount());
    }
}
