<?php

/**
 * Tests for the PeachySQL library.
 * @author Theodore Brown <https://github.com/theodorejb>
 * @version 0.8.0
 */
class DatabaseTableTest extends PHPUnit_Framework_TestCase {

    public function testBuildSelectQueryAllRows() {
        $actual = PeachySQL::buildSelectQuery("TestTable", 'tsql');
        $expected = "SELECT * FROM [TestTable]";
        $this->assertSame($actual["sql"], $expected);
    }

    public function testBuildSelectQueryWhere() {
        $cols = ["username", "password"];

        $where = array(
            "username" => "TestUser",
            "password" => "TestPassword",
            "othercol" => NULL
        );

        $actual = PeachySQL::buildSelectQuery("TestTable", 'mysql', $cols, $where);
        $expected = "SELECT `username`, `password` FROM `TestTable` WHERE "
                . "`username` = ? AND `password` = ? AND `othercol` IS NULL";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('TestUser', 'TestPassword'));
    }

    public function testBuildUpdateQuery() {
        $set = array(
            "username" => "TestUser",
            "othercol" => NULL
        );

        $where = array("id" => 21);

        $actual = PeachySQL::buildUpdateQuery("TestTable", 'tsql', $set, $where);
        $expected = "UPDATE [TestTable] SET [username] = ?, [othercol] = ? "
                . "WHERE [id] = ?";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('TestUser', NULL, 21));
    }

    public function testBuildDeleteQuery() {
        $where = array("id" => 5);
        $actual = PeachySQL::buildDeleteQuery("TestTable", 'tsql', $where);
        $expected = "DELETE FROM [TestTable] WHERE [id] = ?";

        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array(5));
    }

    public function testBuildInsertQuery() {
        $columns = ['col1', 'col2'];
        $values = array(
            array('val1', 'val2'),
            array('val3', 'val4')
        );

        $actual = PeachySQL::buildInsertQuery('TestTable', 'mysql', $columns, $values);
        $expected = "INSERT INTO `TestTable` (`col1`, `col2`) VALUES "
                . "(?,?), (?,?)";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('val1', 'val2', 'val3', 'val4'));
    }

    public function testBuildInsertQueryInsertIDs() {
        $columns = ['col1', 'col2'];
        $values = array(
            array('val1', 'val2'),
            array('val3', 'val4')
        );

        $actual = PeachySQL::buildInsertQuery('TestTable', 'tsql', $columns, $values, "pkColumn");

        $expected = "DECLARE @ids TABLE(RowID int);"
                  . "INSERT INTO [TestTable] ([col1], [col2]) "
                  . "OUTPUT inserted.[pkColumn] INTO @ids(RowID) "
                  . "VALUES (?,?), (?,?);"
                  . "SELECT * FROM @ids;";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('val1', 'val2', 'val3', 'val4'));
    }

}
