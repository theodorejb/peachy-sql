<?php

declare(strict_types=1);

namespace PeachySQL\Test;

use PeachySQL\{Mysql, PeachySql, SqlException, SqlServer};
use PeachySQL\QueryBuilder\SqlParams;
use PeachySQL\Test\src\DbConnector;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Database tests for the PeachySQL library.
 */
class DbTest extends TestCase
{
    private string $table = 'Users';

    public static function tearDownAfterClass(): void
    {
        DbConnector::deleteTestTables();
    }

    /**
     * Returns an array of PeachySQL implementation instances.
     * @return list<array{0: PeachySql}>
     */
    public function dbTypeProvider(): array
    {
        $config = DbConnector::getConfig();
        $implementations = [];

        if ($config->testMysql()) {
            $implementations[] = [new Mysql(DbConnector::getMysqlConn())];
        }

        if ($config->testSqlsrv()) {
            $implementations[] = [new SqlServer(DbConnector::getSqlsrvConn())];
        }

        return $implementations;
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testNoIdentityInsert(PeachySql $peachySql): void
    {
        $peachySql->query("CREATE TABLE Test ( name VARCHAR(50) NOT NULL )");

        // affected count should be zero if no rows are updated
        $this->assertSame(0, $peachySql->updateRows('Test', ['name' => 'test'], ['name' => 'non existent']));

        $colVals = [
            ['name' => 'name1'],
            ['name' => 'name2'],
        ];

        $result = $peachySql->insertRows('Test', $colVals);
        $this->assertSame(2, $result->affected);
        $this->assertCount(0, $result->ids);
        $this->assertSame($colVals, $peachySql->selectFrom("SELECT * FROM Test")->query()->getAll());
        $peachySql->query("DROP TABLE Test");
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testTransactions(PeachySql $peachySql): void
    {
        $peachySql->begin(); // start transaction

        $colVals = [
            'name' => 'George McFly',
            'dob' => '1938-04-01',
            'weight' => 133.8,
            'isDisabled' => true,
            'uuid' => $peachySql->makeBinaryParam(Uuid::uuid4()->getBytes(), 16),
        ];

        $id = $peachySql->insertRow($this->table, $colVals)->id;
        $sql = "SELECT user_id, isDisabled FROM {$this->table} WHERE user_id = ?";
        $result = $peachySql->query($sql, [$id]);

        $this->assertSame(-1, $result->getAffected());
        $this->assertSame(['user_id' => $id, 'isDisabled' => 1], $result->getFirst()); // the row should be selectable

        $peachySql->rollback(); // cancel the transaction
        $sameRow = $peachySql->query($sql, [$id])->getFirst();
        $this->assertSame(null, $sameRow); // the row should no longer exist

        $peachySql->begin(); // start another transaction
        $newId = $peachySql->insertRow($this->table, $colVals)->id;
        $peachySql->commit(); // complete the transaction
        $newRow = $peachySql->selectFrom("SELECT user_id FROM {$this->table}")
            ->where(['user_id' => $newId])->query()->getFirst();

        $this->assertSame(['user_id' => $newId], $newRow); // the row should exist
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testException(PeachySql $peachySql): void
    {
        $badQuery = 'SELECT * FROM nonExistentTable WHERE';

        try {
            $peachySql->query($badQuery); // should throw exception
            $this->fail('Bad query failed to throw exception');
        } catch (SqlException $e) {
            $this->assertSame($badQuery, $e->query);
            $this->assertSame([], $e->params);
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
        }
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testIteratorQuery(PeachySql $peachySql): void
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

        $ids = $peachySql->insertRows($this->table, $insertColVals)->ids;
        $iterator = $peachySql->selectFrom("SELECT * FROM {$this->table}")
            ->where(['user_id' => $ids])->query()->getIterator();

        $this->assertInstanceOf(\Generator::class, $iterator);
        $colValsCompare = [];

        foreach ($iterator as $row) {
            unset($row['user_id']);
            $row['isDisabled'] = (bool)$row['isDisabled'];
            $colValsCompare[] = $row;
        }

        $this->assertSame($colVals, $colValsCompare);

        // use a prepared statement to update both of the rows
        $sql = "UPDATE {$this->table} SET name = ? WHERE user_id = ?";
        $_id = $_name = null;
        $stmt = $peachySql->prepare($sql, [&$_name, &$_id]);

        $realNames = [
            $ids[0] => 'Michael J. Fox',
            $ids[1] => 'Christopher A. Lloyd',
        ];

        foreach ($realNames as $_id => $_name) {
            $stmt->execute();
        }

        $stmt->close();

        $updatedNames = $peachySql->selectFrom("SELECT name FROM {$this->table}")
            ->where(['user_id' => $ids])->query()->getAll();

        $expected = [
            ['name' => $realNames[$ids[0]]],
            ['name' => $realNames[$ids[1]]],
        ];

        $this->assertSame($expected, $updatedNames);
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testInsertBulk(PeachySql $peachySql): void
    {
        $rowCount = 525; // the number of rows to insert/update/delete
        $colVals = [];
        $dob = new \DateTime('1901-01-01');
        $oneDay = new \DateInterval('P1D');

        for ($i = 1; $i <= $rowCount; $i++) {
            $dob->add($oneDay);
            $colVals[] = [
                'name' => 'name' . $i,
                'dob' => $dob->format('Y-m-d'),
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

        $result = $peachySql->insertRows($this->table, $insertColVals);
        $this->assertSame($expectedQueries, $result->queryCount);
        $this->assertSame($rowCount, $result->affected);
        $ids = $result->ids;
        $this->assertSame($rowCount, count($ids));
        $columns = implode(', ', array_keys($colVals[0]));

        $rows = $peachySql->selectFrom("SELECT {$columns} FROM {$this->table}")
            ->where(['user_id' => $ids])->query()->getAll();

        $this->assertSame($colVals, $rows);

        // update the inserted rows
        $numUpdated = $peachySql->updateRows($this->table, ['name' => 'updated'], ['user_id' => $ids]);
        $this->assertSame($rowCount, $numUpdated);

        // update a binary column
        $newUuid = Uuid::uuid4()->getBytes();
        $userId = $ids[0];
        $set = ['uuid' => $peachySql->makeBinaryParam($newUuid)];
        $peachySql->updateRows($this->table, $set, ['user_id' => $userId]);
        /** @var array{uuid: string} $updatedRow */
        $updatedRow = $peachySql->selectFrom("SELECT uuid FROM {$this->table}")
            ->where(['user_id' => $userId])->query()->getFirst();
        $this->assertSame($newUuid, $updatedRow['uuid']);

        // delete the inserted rows
        $numDeleted = $peachySql->deleteFrom($this->table, ['user_id' => $ids]);
        $this->assertSame($rowCount, $numDeleted);
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testEmptyBulkInsert(PeachySql $peachySql): void
    {
        $result = $peachySql->insertRows($this->table, []);
        $this->assertSame(0, $result->affected);
        $this->assertSame(0, $result->queryCount);
        $this->assertEmpty($result->ids);
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testSelectFromBinding(PeachySql $peachySql): void
    {
        $row = ['name' => 'Test User', 'dob' => '2000-01-01', 'weight' => 123, 'isDisabled' => true];
        $id = $peachySql->insertRow($this->table, $row)->id;

        $result = $peachySql->select(new SqlParams("SELECT name, ? AS bound FROM {$this->table}", ['value']))
            ->where(['user_id' => $id])->query()->getFirst();

        $this->assertSame(['name' => 'Test User', 'bound' => 'value'], $result);

        // delete the inserted row
        $numDeleted = $peachySql->deleteFrom($this->table, ['user_id' => $id]);
        $this->assertSame(1, $numDeleted);
    }
}
