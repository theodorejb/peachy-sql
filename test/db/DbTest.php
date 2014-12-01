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
            PeachySQL::OPT_TABLE => 'Users',
            PeachySQL::OPT_COLUMNS => ['user_id', 'fname', 'lname', 'dob'],
        ];

        if ($config["testWith"]["mysql"]) {
            $implementations[] = [new Mysql(TestDbConnector::getMysqlConn(), $options)];
        }

        if ($config["testWith"]["sqlsrv"]) {
            $tsqlOptions = array_merge($options, [TSQL::OPT_IDCOL => 'user_id']);
            $implementations[] = [new TSQL(TestDbConnector::getSqlsrvConn(), $tsqlOptions)];
        }

        return $implementations;
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testTransactions(PeachySQL $peachySql)
    {
        $peachySql->begin(); // start transaction

        $colVals = [
            'fname' => 'Theodore',
            'lname' => 'Brown',
            'dob' => date('Y-m-d', strtotime('tomorrow'))
        ];

        $peachySql->insertAssoc($colVals, function ($id) use ($peachySql, $colVals) {
            $rows = $peachySql->select(['user_id'], ['user_id' => $id]);
            $this->assertSame([['user_id' => $id]], $rows); // the row should be selectable
            $peachySql->rollback(); // cancel the transaction

            $sameRows = $peachySql->select([], ['user_id' => $id]);
            $this->assertSame([], $sameRows); // the row should no longer exist

            $peachySql->begin(); // start another transaction
            $newId = $peachySql->insertAssoc($colVals);
            $peachySql->commit(); // complete the transaction
            $newRows = $peachySql->select(['user_id'], ['user_id' => $newId]);
            $this->assertSame([['user_id' => $newId]], $newRows); // the row should exist
        });
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testInsertAssoc(PeachySQL $peachySql)
    {
        $colVals = [
            'fname' => 'Theodore',
            'lname' => 'Brown',
            'dob' => date('Y-m-d', strtotime('tomorrow'))
        ];

        $peachySql->insertAssoc($colVals, function ($id, SqlResult $result) use ($peachySql) {
            $this->assertInternalType("int", $id);

            $peachySql->delete(["user_id" => $id], function (SqlResult $result) {
                $this->assertSame(1, $result->getAffected());
            });
        });
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testException(PeachySQL $peachySql)
    {
        $badQuery = 'SELECT * FROM nonExistentTable WHERE';

        try {
            $peachySql->query($badQuery); // should throw exception
        } catch (SqlException $e) {
            $this->assertSame($badQuery, $e->getQuery());
        }
    }

    /**
     * @dataProvider dbTypeProvider
     */
    public function testBasic(PeachySQL $peachySql)
    {
        $cols = ['fname', 'lname', 'dob'];

        $rowCount = 500; // the number of rows to insert/update/delete
        $expected = []; // expected result when selecting inserted rows

        for ($i = 1; $i <= $rowCount; $i++) {
            $fname = 'fname' . $i;
            $lname = 'lname' . $i;
            $year = (1900 + $i) . '-01-01';

            $vals[] = [$fname, $lname, $year];

            $expected[] = [
                'fname' => $fname,
                'lname' => $lname,
                'dob' => $year
            ];
        }

        $peachySql->insert($cols, $vals, function ($ids, SqlResult $result) use ($peachySql, $rowCount, $expected, $cols) {
            $this->assertGreaterThan(0, $result->getAffected());
            $this->assertSame($rowCount, count($ids));

            $rows = $peachySql->select($cols, ['user_id' => $ids]);
            $this->assertSame($expected, $rows);

            // update the inserted rows
            $numUpdated = $peachySql->update(['lname' => 'updated'], ['user_id' => $ids]);
            $this->assertSame($rowCount, $numUpdated);

            // delete the inserted rows
            $numDeleted = $peachySql->delete(['user_id' => $ids]);
            $this->assertSame($rowCount, $numDeleted);
        });
    }
}
