<?php

namespace PeachySQL;

/**
 * Tests for the SQLResult object
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class SQLResultTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRows()
    {
        $exampleRows = [
            ["id" => 1, "name" => "foo"],
            ["id" => 2, "name" => "bar"],
        ];

        $result = new SQLResult($exampleRows, 0, "");
        $this->assertSame($exampleRows, $result->getAll());
        $this->assertSame($exampleRows[0], $result->getFirst());
        $this->assertSame($result->getAll(), $result->getRows()); // alias

        $noRowsResult = new SQLResult([], 0, "");
        $this->assertSame([], $noRowsResult->getAll());
        $this->assertSame(null, $noRowsResult->getFirst());
    }
}
