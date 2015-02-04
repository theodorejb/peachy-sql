<?php

namespace PeachySQL;

/**
 * Database tests for the PeachySQL library.
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class DbTest extends \PHPUnit_Framework_TestCase
{
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

        $options = [
            PeachySql::OPT_TABLE => 'Users',
            PeachySql::OPT_COLUMNS => ['user_id', 'fname', 'lname', 'dob'],
        ];

        if ($config['testWith']['mysql']) {
            $implementations[] = [new Mysql(TestDbConnector::getMysqlConn(), $options)];
        }

        if ($config['testWith']['sqlsrv']) {
            $sqlServerOptions = array_merge($options, [SqlServer::OPT_IDCOL => 'user_id']);
            $implementations[] = [new SqlServer(TestDbConnector::getSqlsrvConn(), $sqlServerOptions)];
        }

        return $implementations;
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testTransactions(PeachySql $peachySql)
    {
        $peachySql->begin(); // start transaction

        $colVals = [
            'fname' => 'Theodore',
            'lname' => 'Brown',
            'dob' => date('Y-m-d', strtotime('tomorrow'))
        ];

        $id = $peachySql->insertOne($colVals)->getId();
        $this->assertInternalType('int', $id);

        $sql = 'SELECT user_id FROM Users WHERE user_id = ?';
        $row = $peachySql->query($sql, [$id])->getFirst();
        $this->assertSame(['user_id' => $id], $row); // the row should be selectable

        $peachySql->rollback(); // cancel the transaction
        $sameRow = $peachySql->query($sql, [$id])->getFirst();
        $this->assertSame(null, $sameRow); // the row should no longer exist

        $peachySql->begin(); // start another transaction
        $newId = $peachySql->insertAssoc($colVals);
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
            ['fname' => 'fname1', 'lname' => 'lname2', 'dob' => date('Y-m-d', strtotime('yesterday'))],
            ['fname' => 'fname1', 'lname' => 'lname2', 'dob' => date('Y-m-d', strtotime('next monday'))],
        ];

        $ids = $peachySql->insertBulk($colVals)->getIds();
        $placeholders = substr_replace(str_repeat('?,', count($ids)), '', -1);
        $sql = "SELECT * FROM Users WHERE user_id IN ($placeholders)";

        $iterator = $peachySql->query($sql, $ids)->getIterator();
        $this->assertInstanceOf('Generator', $iterator);
        $colValsCompare = [];

        foreach ($iterator as $row) {
            unset($row['user_id']);
            $colValsCompare[] = $row;
        }

        $this->assertSame($colVals, $colValsCompare);
        $affected = $peachySql->delete(['user_id' => $ids]);
        $this->assertSame(count($colVals), $affected);
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testInsert(PeachySql $peachySql)
    {
        $cols = ['fname', 'lname', 'dob'];

        $vals = [
            ['fname1', 'lname1', '2014-12-01'],
            ['fname2', 'lname2', '2014-12-02'],
        ];

        $ids = $peachySql->insert($cols, $vals);
        $this->assertSameSize($vals, $ids);
        $affected = $peachySql->delete(['user_id' => $ids]);
        $this->assertSame(count($vals), $affected);
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testInsertBulk(PeachySql $peachySql)
    {
        $rowCount = 700; // the number of rows to insert/update/delete
        $colVals = [];

        for ($i = 1; $i <= $rowCount; $i++) {
            $fname = 'fname' . $i;
            $lname = 'lname' . $i;
            $year = (1900 + $i) . '-01-01';

            $colVals[] = [
                'fname' => $fname,
                'lname' => $lname,
                'dob' => $year
            ];
        }

        if ($peachySql instanceof SqlServer) {
            $expectedQueries = 2;
        } else {
            $expectedQueries = 1;
        }

        $result = $peachySql->insertBulk($colVals);
        $this->assertSame($expectedQueries, $result->getQueryCount());
        $this->assertSame($rowCount, $result->getAffected());
        $ids = $result->getIds();
        $this->assertSame($rowCount, count($ids));

        $rows = $peachySql->select(array_keys($colVals[0]), ['user_id' => $ids]);
        $this->assertSame($colVals, $rows);

        // update the inserted rows
        $numUpdated = $peachySql->update(['lname' => 'updated'], ['user_id' => $ids]);
        $this->assertSame($rowCount, $numUpdated);

        // delete the inserted rows
        $numDeleted = $peachySql->delete(['user_id' => $ids]);
        $this->assertSame($rowCount, $numDeleted);
    }
}
