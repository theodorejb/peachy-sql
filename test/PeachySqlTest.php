<?php

namespace PeachySQL;

/**
 * Tests for the PeachySQL library.
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class PeachySqlTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildSelectQueryAllRows()
    {
        $actual = PeachySql::buildSelectQuery("TestTable");
        $expected = "SELECT * FROM TestTable";
        $this->assertSame($expected, $actual["sql"]);
    }

    public function testBuildSelectQueryWhere()
    {
        $cols = ["username", "password"];

        $where = [
            "username" => "TestUser",
            "password" => "TestPassword",
            "othercol" => null
        ];

        $validCols = array_keys($where);

        $actual = PeachySql::buildSelectQuery("TestTable", $cols, $validCols, $where);
        $expected = "SELECT username, password FROM TestTable WHERE "
                  . "username = ? AND password = ? AND othercol IS NULL";
        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame(['TestUser', 'TestPassword'], $actual["params"]);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildSelectQueryInvalidColumns()
    {
        PeachySql::buildSelectQuery("TestTable", ["fizzbuzz"], ["foo", "bar"]);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildSelectQueryInvalidColumnsInWhere()
    {
        PeachySql::buildSelectQuery("TestTable", ["foo"], ["foo", "bar"], ["fizzbuzz" => null]);
    }

    public function testBuildUpdateQuery()
    {
        $set = [
            "username" => "TestUser",
            "othercol" => null
        ];

        $where = ["id" => 21];
        $actual = PeachySql::buildUpdateQuery("TestTable", $set, $where, ["id", "username", "othercol"]);
        $expected = "UPDATE TestTable SET username = ?, othercol = ? WHERE id = ?";

        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame(['TestUser', null, 21], $actual["params"]);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildUpdateQueryInvalidColumns()
    {
        PeachySql::buildUpdateQuery("TestTable", ["fizzbuzz" => null], ["bar" => 1], ["foo", "bar"]);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildUpdateQueryInvalidColumnsInWhere()
    {
        PeachySql::buildUpdateQuery("TestTable", ["foo" => null], ["fizzbuzz" => 1], ["foo", "bar"]);
    }

    public function testBuildDeleteQuery()
    {
        $where = ["id" => 5, "username" => ["tester", "tester2"]];
        $actual = PeachySql::buildDeleteQuery("TestTable", $where, ["id", "username"]);
        $expected = "DELETE FROM TestTable WHERE id = ? AND username IN(?,?)";

        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame([5, "tester", "tester2"], $actual["params"]);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testBuildDeleteQueryInvalidColumnsInWhere()
    {
        PeachySql::buildDeleteQuery("TestTable", ["fizzbuzz" => null], ["foo", "bar"]);
    }
}
