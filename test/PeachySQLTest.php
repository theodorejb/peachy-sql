<?php

/**
 * Tests for the PeachySQL library.
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class PeachySQLTest extends PHPUnit_Framework_TestCase {

    public function testBuildSelectQueryAllRows() {
        $actual = PeachySQL::buildSelectQuery("TestTable");
        $expected = "SELECT * FROM TestTable";
        $this->assertSame($expected, $actual["sql"]);
    }

    public function testBuildSelectQueryWhere() {
        $cols = ["username", "password"];

        $where = [
            "username" => "TestUser",
            "password" => "TestPassword",
            "othercol" => NULL
        ];

        $actual = PeachySQL::buildSelectQuery("TestTable", $cols, $where);
        $expected = "SELECT username, password FROM TestTable WHERE "
                  . "username = ? AND password = ? AND othercol IS NULL";
        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame(['TestUser', 'TestPassword'], $actual["params"]);
    }

    public function testBuildUpdateQuery() {
        $set = [
            "username" => "TestUser",
            "othercol" => NULL
        ];

        $where = ["id" => 21];

        $actual = PeachySQL::buildUpdateQuery("TestTable", $set, $where);
        $expected = "UPDATE TestTable SET username = ?, othercol = ? "
                  . "WHERE id = ?";
        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame(['TestUser', NULL, 21], $actual["params"]);
    }

    public function testBuildDeleteQuery() {
        $where = ["id" => 5, "username" => ["tester", "tester2"]];
        $actual = PeachySQL::buildDeleteQuery("TestTable", $where);
        $expected = "DELETE FROM TestTable WHERE id = ? "
                  . "AND username IN(?,?)";

        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame([5, "tester", "tester2"], $actual["params"]);
    }

    public function testBuildInsertQuery() {
        $columns = ['col1', 'col2', 'col3'];
        $values = [['val1', 'val2', 'val3']];

        $actual = PeachySQL::buildInsertQuery('TestTable', PeachySQL::DBTYPE_MYSQL, $columns, $values);
        $expected = "INSERT INTO TestTable (col1, col2, col3) VALUES (?,?,?)";
        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame(['val1', 'val2', 'val3'], $actual["params"]);
    }

    public function testBuildInsertQueryTsqlInsertIDs() {
        $columns = ['col1', 'col2'];

        // a two-dimensional array should insert multiple rows
        $values = [
            ['val1', 'val2'],
            ['val3', 'val4']
        ];

        $actual = PeachySQL::buildInsertQuery('TestTable', PeachySQL::DBTYPE_TSQL, $columns, $values, "pkColumn");

        $expected = "DECLARE @ids TABLE(RowID int);"
                  . "INSERT INTO TestTable (col1, col2) "
                  . "OUTPUT inserted.pkColumn INTO @ids(RowID) "
                  . "VALUES (?,?), (?,?);"
                  . "SELECT * FROM @ids;";
        $this->assertSame($expected, $actual["sql"]);
        $this->assertSame(['val1', 'val2', 'val3', 'val4'], $actual["params"]);
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

        $peoplePetsAssoc = [];

        // the callback should be called once per person
        PeachySQL::splitRows($peoplePets, "name", function ($personPets) use (&$peoplePetsAssoc) {
            foreach ($personPets as $personPet) {
                $petsArray[] = $personPet["petName"];
            }

            $person = $personPets[0]["name"];
            $peoplePetsAssoc[$person] = $petsArray;
        });

        $this->assertSame($expected, $peoplePetsAssoc);
    }

}
