<?php

/**
 * Tests for the DatabaseTable class.
 * @author Theodore Brown <https://github.com/theodorejb>
 * @version 0.5
 */
class DatabaseTableTest extends PHPUnit_Framework_TestCase {

    public function testBuildSelectQueryAllRows() {
        $actual = PeachySQL::buildSelectQuery("TestTable");
        $expected = "SELECT * FROM [TestTable]";
        $this->assertSame($actual["sql"], $expected);
    }

    public function testBuildSelectQueryColumnVals() {
        $columnVals = array(
            "username" => "TestUser",
            "password" => "TestPassword",
            "othercol" => NULL
        );

        $actual = PeachySQL::buildSelectQuery("TestTable", $columnVals);
        $expected = "SELECT * FROM [TestTable] WHERE "
                . "[username] = ? AND [password] = ? AND [othercol] IS NULL";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('TestUser', 'TestPassword'));
    }

    public function testBuildUpdateQuery() {
        $set = array(
            "username" => "TestUser",
            "othercol" => NULL
        );

        $where = array("id" => 21);

        $actual = PeachySQL::buildUpdateQuery("TestTable", $set, $where);
        $expected = "UPDATE [TestTable] SET [username] = ?, [othercol] = ? "
                . "WHERE [id] = ?";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('TestUser', NULL, 21));
    }

    public function testBuildDeleteQuery() {
        $where = array("id" => 5);
        $actual = PeachySQL::buildDeleteQuery("TestTable", $where);
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

        $actual = PeachySQL::buildInsertQuery('TestTable', $columns, $values);
        $expected = "INSERT INTO [TestTable] ([col1], [col2]) VALUES "
                . "(?,?), (?,?);";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('val1', 'val2', 'val3', 'val4'));
    }

    public function testBuildInsertQueryInsertIDs() {
        $columns = ['col1', 'col2'];
        $values = array(
            array('val1', 'val2'),
            array('val3', 'val4')
        );

        $actual = PeachySQL::buildInsertQuery('TestTable', $columns, $values, "pkColumn");

        $expected = "DECLARE @ids TABLE(RowID int);"
                  . "INSERT INTO [TestTable] ([col1], [col2]) "
                  . "OUTPUT inserted.[pkColumn] INTO @ids(RowID) "
                  . "VALUES (?,?), (?,?);"
                  . "SELECT * FROM @ids;";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('val1', 'val2', 'val3', 'val4'));
    }

}
