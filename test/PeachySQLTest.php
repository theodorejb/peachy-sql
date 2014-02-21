<?php

/**
 * Tests for the PeachySQL library.
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class DatabaseTableTest extends PHPUnit_Framework_TestCase {

    public function testBuildSelectQueryAllRows() {
        $actual = PeachySQL::buildSelectQuery("TestTable");
        $expected = "SELECT * FROM TestTable";
        $this->assertSame($actual["sql"], $expected);
    }

    public function testBuildSelectQueryWhere() {
        $cols = ["username", "password"];

        $where = array(
            "username" => "TestUser",
            "password" => "TestPassword",
            "othercol" => NULL
        );

        $actual = PeachySQL::buildSelectQuery("TestTable", $cols, $where);
        $expected = "SELECT username, password FROM TestTable WHERE "
                  . "username = ? AND password = ? AND othercol IS NULL";
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
        $expected = "UPDATE TestTable SET username = ?, othercol = ? "
                  . "WHERE id = ?";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('TestUser', NULL, 21));
    }

    public function testBuildDeleteQuery() {
        $where = array("id" => 5, "username" => ["tester", "tester2"]);
        $actual = PeachySQL::buildDeleteQuery("TestTable", $where);
        $expected = "DELETE FROM TestTable WHERE id = ? "
                  . "AND username IN(?,?)";

        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array(5, "tester", "tester2"));
    }

    public function testBuildInsertQuery() {
        $columns = ['col1', 'col2'];
        $values = array(
            array('val1', 'val2'),
            array('val3', 'val4')
        );

        $actual = PeachySQL::buildInsertQuery('TestTable', PeachySQL::DBTYPE_MYSQL, $columns, $values);
        $expected = "INSERT INTO TestTable (col1, col2) "
                  . "VALUES (?,?), (?,?)";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('val1', 'val2', 'val3', 'val4'));
    }

    public function testBuildInsertQueryTsqlInsertIDs() {
        $columns = ['col1', 'col2'];
        $values = array(
            array('val1', 'val2'),
            array('val3', 'val4')
        );

        $actual = PeachySQL::buildInsertQuery('TestTable', PeachySQL::DBTYPE_TSQL, $columns, $values, "pkColumn");

        $expected = "DECLARE @ids TABLE(RowID int);"
                  . "INSERT INTO TestTable (col1, col2) "
                  . "OUTPUT inserted.pkColumn INTO @ids(RowID) "
                  . "VALUES (?,?), (?,?);"
                  . "SELECT * FROM @ids;";
        $this->assertSame($actual["sql"], $expected);
        $this->assertSame($actual["params"], array('val1', 'val2', 'val3', 'val4'));
    }
    
    public function testSplitRows() {
        // an array retrived by joining people and pets tables
        $peoplePets = [
            ["name" => "Jack", "petName" => "Scruffy"],
            ["name" => "Jack", "petName" => "Spot"],
            ["name" => "Jack", "petName" => "Paws"],
            ["name" => "Amy", "petName" => "Blackie"],
            ["name" => "Amy", "petName" => "Whiskers"]
        ];
        
        $expected = [
            "Jack" => [
                "Scruffy",
                "Spot",
                "Paws"
            ],
            "Amy" => [
                "Blackie",
                "Whiskers"
            ]
        ];
        
        $actual = [];
        
        // the callback should be called once per person
        PeachySQL::splitRows($peoplePets, "name", function ($personPets) use (&$actual) {
            $person = $personPets[0]["name"];
            $petsArray = [];

            foreach ($personPets as $personPet) {
                $petsArray[] = $personPet["petName"];
            }
            
            $actual[$person] = $petsArray;
        });

        $this->assertSame($expected, $actual);
    }

}
