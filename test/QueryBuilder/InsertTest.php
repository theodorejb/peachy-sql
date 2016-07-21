<?php

namespace PeachySQL\QueryBuilder;

/**
 * Tests for the Insert query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class InsertTest extends \PHPUnit_Framework_TestCase
{
    public function batchRowsTestCases()
    {
        $colVals = [
            [
                'column1' => 'test',
                'column2' => 'test2'
            ],
            [
                'column1' => 'test3',
                'column2' => 'test4'
            ],
            [
                'column1' => 'test5',
                'column2' => 'test6'
            ],
            [
                'column1' => 'test7',
                'column2' => 'test8'
            ],
        ];

        $firstTwoRows = [$colVals[0], $colVals[1]];
        $lastTwoRows = [$colVals[2], $colVals[3]];
        $firstThreeRows = [$colVals[0], $colVals[1], $colVals[2]];
        $lastRow = [$colVals[3]];

        return [
            [$colVals, 0, 0, [$colVals]], // one query
            [$colVals, 1000, 1000, [$colVals]], // one query
            [$colVals, 8, 4, [$colVals]], // one query
            [$colVals, 4, 0, [$firstTwoRows, $lastTwoRows]], // max of 4 bound params = 2 rows at a time
            [$colVals, 6, 1000, [$firstThreeRows, $lastRow]], // max of 6 bound params = 3 rows at a time
            [$colVals, 0, 3, [$firstThreeRows, $lastRow]], // 3 rows at a time max
            [$colVals, 6, 2, [$firstTwoRows, $lastTwoRows]], // six bound params max overridden by max of two rows
        ];
    }

    /**
     * @dataProvider batchRowsTestCases
     */
    public function testBatchRows(array $colVals, $maxParams, $maxRows, $expected)
    {
        $result = Insert::batchRows($colVals, $maxParams, $maxRows);
        $this->assertSame($expected, $result);
    }

    public function testBuildQuery()
    {
        $colVals = [
            'col1' => 'val1',
            'col2' => 'val2',
            'col3' => 'val3',
        ];

        $actual = (new Insert(new \PeachySQL\Mysql\Options()))->buildQuery('TestTable', [$colVals]);
        $expected = 'INSERT INTO TestTable (`col1`, `col2`, `col3`) VALUES (?,?,?)';
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['val1', 'val2', 'val3'], $actual->getParams());
    }

    /**
     * Tests building an insert query with SCOPE_IDENTITY to retrieve the insert ID
     */
    public function testBuildQueryWithScopeIdentity()
    {
        $colVals = [
            [
                'col1' => 'foo1',
                'col2' => 'foo2',
            ],
            [
                'col1' => 'bar1',
                'col2' => 'bar2',
            ],
        ];

        $actual = (new Insert(new \PeachySQL\SqlServer\Options()))->buildQuery('TestTable', $colVals);
        $expected = 'INSERT INTO TestTable ("col1", "col2")'
            . ' VALUES (?,?), (?,?);'
            . ' SELECT SCOPE_IDENTITY() AS RowID;';
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['foo1', 'foo2', 'bar1', 'bar2'], $actual->getParams());
    }

    /**
     * Tests building an insert query with OUTPUT clause for SQL Server
     */
    public function testBuildQueryWithOutputClause()
    {
        $colVals = [
            [
                'col1' => 'foo1',
                'col2' => 'foo2',
            ],
            [
                'col1' => 'bar1',
                'col2' => 'bar2',
            ],
        ];

        $options = new \PeachySQL\SqlServer\Options();
        $options->setIdColumn('pkColumn');

        $actual = (new Insert($options))->buildQuery('TestTable', $colVals, true);
        $expected = 'DECLARE @ids TABLE(RowID int);'
            . ' INSERT INTO TestTable ("col1", "col2")'
            . ' OUTPUT inserted."pkColumn" INTO @ids(RowID)'
            . ' VALUES (?,?), (?,?);'
            . ' SELECT * FROM @ids;';
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['foo1', 'foo2', 'bar1', 'bar2'], $actual->getParams());
    }
}
