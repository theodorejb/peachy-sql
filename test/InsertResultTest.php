<?php

namespace PeachySQL;

/**
 * Tests for the InsertResult object
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class InsertResultTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRetrieve()
    {
        $result = new InsertResult(24, 2);
        $this->assertSame(24, $result->getId());
        $this->assertSame(2, $result->getAffected());
    }
}
