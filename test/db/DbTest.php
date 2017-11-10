<?php

namespace PeachySQL;

use Ramsey\Uuid\Uuid;

/**
 * Database tests for the PeachySQL library.
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class DbTest extends \PHPUnit_Framework_TestCase
{
    CONST TABLE_NAME = 'Users';

    public static function tearDownAfterClass()
    {
        TestDbConnector::deleteTestTables();
    }

    /**
     * Returns an array of PeachySQL implementation instances.
     */
    public function dbTypeProvider()
    {
        $config = TestDbConnector::getConfig();
        $implementations = [];

        if ($config['testWith']['mysql']) {
            $mysqlOptions = new Mysql\Options();
            $mysqlOptions->setTable(self::TABLE_NAME);
            $implementations[] = [new Mysql(TestDbConnector::getMysqlConn(), $mysqlOptions)];
        }

        if ($config['testWith']['sqlsrv']) {
            $sqlServerOptions = new SqlServer\Options();
            $sqlServerOptions->setTable(self::TABLE_NAME);
            $sqlServerOptions->setIdColumn('user_id');
            $implementations[] = [new SqlServer(TestDbConnector::getSqlsrvConn(), $sqlServerOptions)];
        }

        return $implementations;
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testNoIdentityInsert(PeachySql $peachySql)
    {
        $peachySql->query("CREATE TABLE Test ( name VARCHAR(50) NOT NULL )");

        // affected count should be zero if no rows are updated
        $this->assertSame(0, $peachySql->updateRows('Test', ['name' => 'test'], ['name' => 'non existent']));

        $colVals = [
            ['name' => 'name1'],
            ['name' => 'name2'],
        ];

        $result = $peachySql->insertRows('Test', $colVals);
        $this->assertSame(2, $result->getAffected());
        $this->assertEmpty($result->getIds());
        $this->assertSame($colVals, $peachySql->selectFrom("SELECT * FROM Test")->query()->getAll());
        $peachySql->query("DROP TABLE Test");
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testTransactions(PeachySql $peachySql)
    {
        $peachySql->begin(); // start transaction

        $colVals = [
            'name' => 'George McFly',
            'dob' => '1938-04-01',
            'weight' => 133.8,
            'isDisabled' => true,
            'uuid' => $peachySql->makeBinaryParam(Uuid::uuid4()->getBytes(), 16),
        ];

        $id = $peachySql->insertOne($colVals)->getId();
        $this->assertInternalType('int', $id);

        $sql = 'SELECT user_id, isDisabled FROM Users WHERE user_id = ?';
        $result = $peachySql->query($sql, [$id]);

        $this->assertSame(-1, $result->getAffected());
        $this->assertSame(['user_id' => $id, 'isDisabled' => 1], $result->getFirst()); // the row should be selectable

        $peachySql->rollback(); // cancel the transaction
        $sameRow = $peachySql->query($sql, [$id])->getFirst();
        $this->assertSame(null, $sameRow); // the row should no longer exist

        $peachySql->begin(); // start another transaction
        $newId = $peachySql->insertOne($colVals)->getId();
        $peachySql->commit(); // complete the transaction
        $newRows = $peachySql->select(['user_id'], ['user_id' => $newId]);
        $this->assertSame([['user_id' => $newId]], $newRows); // the row should exist
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testException(PeachySql $peachySql)
    {
        $badQuery = 'SELECT * FROM nonExistentTable WHERE';

        try {
            $peachySql->query($badQuery); // should throw exception
        } catch (SqlException $e) {
            $this->assertSame($badQuery, $e->getQuery());
            $this->assertSame('42000', $e->getSqlState());

            if ($peachySql instanceof SqlServer) {
                $this->assertSame(102, $e->getCode());
                $this->assertStringEndsWith("Incorrect syntax near 'WHERE'.", $e->getMessage());
            } else {
                $this->assertSame(1064, $e->getCode());
                $expectedMessage = "Failed to prepare statement: You have an error in your"
                    . " SQL syntax; check the manual that corresponds to your MySQL server"
                    . " version for the right syntax to use near '' at line 1";
                $this->assertSame($expectedMessage, $e->getMessage());
            }

            return;
        }

        $this->fail('Bad query failed to throw exception');
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testIteratorQuery(PeachySql $peachySql)
    {
        $colVals = [
            ['name' => 'Martin S. McFly', 'dob' => '1968-06-20', 'weight' => 140.7, 'isDisabled' => true, 'uuid' => Uuid::uuid4()->getBytes()],
            ['name' => 'Emmett L. Brown', 'dob' => '1920-01-01', 'weight' => 155.4, 'isDisabled' => false, 'uuid' => null],
        ];

        $insertColVals = [];

        foreach ($colVals as $row) {
            $row['uuid'] = $peachySql->makeBinaryParam($row['uuid']);
            $insertColVals[] = $row;
        }

        $ids = $peachySql->insertRows(self::TABLE_NAME, $insertColVals)->getIds();
        $iterator = $peachySql->selectFrom("SELECT * FROM Users")
            ->where(['user_id' => $ids])->query()->getIterator();

        $this->assertInstanceOf('Generator', $iterator);
        $colValsCompare = [];

        foreach ($iterator as $row) {
            unset($row['user_id']);
            $row['weight'] = round($row['weight'], 1); // so that float comparison will work in HHVM
            $row['isDisabled'] = (bool)$row['isDisabled'];
            $colValsCompare[] = $row;
        }

        $this->assertSame($colVals, $colValsCompare);

        // use a prepared statement to update both of the rows
        $sql = "UPDATE Users SET name = ? WHERE user_id = ?";
        $id = $name = null;
        $stmt = $peachySql->prepare($sql, [&$name, &$id]);

        $realNames = [
            $ids[0] => 'Michael J. Fox',
            $ids[1] => 'Christopher A. Lloyd',
        ];

        foreach ($realNames as $id => $name) {
            $stmt->execute();
        }

        $stmt->close();
        $updatedNames = $peachySql->select(['name'], ['user_id' => $ids]);
        $expected = [
            ['name' => $realNames[$ids[0]]],
            ['name' => $realNames[$ids[1]]],
        ];

        $this->assertSame($expected, $updatedNames);
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testInsertBulk(PeachySql $peachySql)
    {
        $rowCount = 525; // the number of rows to insert/update/delete
        $colVals = [];

        for ($i = 1; $i <= $rowCount; $i++) {
            $colVals[] = [
                'name' => 'name' . $i,
                'dob' => (1900 + $i) . '-01-01',
                'weight' => round(rand(1001, 2899) / 10, 1),
                'isDisabled' => 0,
                'uuid' => Uuid::uuid4()->getBytes(),
            ];
        }

        $insertColVals = [];
        $expectedQueries = ($peachySql instanceof SqlServer) ? 2 : 1;

        foreach ($colVals as $row) {
            $row['uuid'] = $peachySql->makeBinaryParam($row['uuid']);
            $insertColVals[] = $row;
        }

        $result = $peachySql->insertBulk($insertColVals);
        $this->assertSame($expectedQueries, $result->getQueryCount());
        $this->assertSame($rowCount, $result->getAffected());
        $ids = $result->getIds();
        $this->assertSame($rowCount, count($ids));

        $rows = $peachySql->select(array_keys($colVals[0]), ['user_id' => $ids]);
        array_walk($rows, function (&$row) {
            $row['weight'] = round($row['weight'], 1); // so that float comparison will work in HHVM
        });

        $this->assertSame($colVals, $rows);

        // update the inserted rows
        $numUpdated = $peachySql->update(['name' => 'updated'], ['user_id' => $ids]);
        $this->assertSame($rowCount, $numUpdated);

        // delete the inserted rows
        $numDeleted = $peachySql->delete(['user_id' => $ids]);
        $this->assertSame($rowCount, $numDeleted);
    }
}
