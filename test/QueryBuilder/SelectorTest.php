<?php

namespace PeachySQL\QueryBuilder;

/**
 * Tests for the Select query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class SelectorTest extends \PHPUnit_Framework_TestCase
{
    public function testWhere()
    {
        $query = "SELECT `username`, `password` FROM TestTable";
        $selector = new Selector($query, new \PeachySQL\Mysql\Options());

        $where = [
            'username' => 'TestUser',
            'password' => ['eq' => ['TestPassword', 'Password123']],
            'othercol' => null,
            'datecol' => ['ge' => '2016-05-01', 'lt' => '2016-06-01'],
            'firstname' => ['nl' => ['%dore', '%rah']],
        ];

        $actual = $selector->where($where, true)->getSqlParams();
        $expected = 'SELECT `username`, `password` FROM TestTable WHERE '
            . '`username` = ? AND `password` IN(?,?) AND `othercol` IS NULL'
            . ' AND `datecol` >= ? AND `datecol` < ?'
            . ' AND `firstname` NOT LIKE ? AND `firstname` NOT LIKE ?';
        $this->assertSame($expected, $actual->getSql());
        $params = ['TestUser', 'TestPassword', 'Password123', '2016-05-01', '2016-06-01', '%dore', '%rah'];
        $this->assertSame($params, $actual->getParams());
    }

    public function testInvalidWhere()
    {
        $query = new Query(new \PeachySQL\SqlServer\Options());

        try {
            $where = [ 'testcol' => [] ];
            $query->buildWhereClause($where);
            $this->fail('Failed to throw exception for empty filter conditions');
        } catch (\Exception $e) {
            $this->assertSame('Filter conditions cannot be empty for [testcol] column', $e->getMessage());
        }

        try {
            $where = [ 'testcol' => ['foo' => 'bar'] ];
            $query->buildWhereClause($where);
            $this->fail('Failed to throw exception for invalid comparison operator');
        } catch (\Exception $e) {
            $this->assertSame('foo is not a valid operator', $e->getMessage());
        }

        try {
            $where = [ 'testcol' => ['gt' => [3, 4]] ];
            $query->buildWhereClause($where);
            $this->fail('Failed to throw exception for invalid comparison value');
        } catch (\Exception $e) {
            $this->assertSame('gt operator cannot be used with an array', $e->getMessage());
        }
    }

    public function testOrderBy()
    {
        $query = "SELECT [username], [lastname] FROM TestTable";
        $select = new Selector($query, new \PeachySQL\SqlServer\Options());
        $actual = $select->orderBy(['lastname', 'firstname'], true)->getSqlParams();

        $expected = "{$query} ORDER BY [lastname], [firstname]";
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame([], $actual->getParams());

        $orderBy = ['lastname' => 'asc', 'firstname' => 'asc', 'age' => 'desc'];
        $actual = (new Selector($query, new \PeachySQL\SqlServer\Options()))->orderBy($orderBy)->getSqlParams();
        $this->assertSame("{$query} ORDER BY lastname ASC, firstname ASC, age DESC", $actual->getSql());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage nonsense is not a valid sort direction for column `testcol`. Use asc or desc.
     */
    public function testInvalidOrderBy()
    {
        $select = new Select(new \PeachySQL\Mysql\Options());
        $select->buildOrderByClause(['testcol' => 'nonsense']);
    }

    public function testBuildPagination()
    {
        $mySqlSelect = new Select(new \PeachySQL\Mysql\Options());
        $sqlsrvSelect = new Select(new \PeachySQL\SqlServer\Options());

        $mySqlPage1 = $mySqlSelect->buildPagination(25, 0);
        $this->assertSame('LIMIT 25 OFFSET 0', $mySqlPage1);

        $sqlsrvPage1 = $sqlsrvSelect->buildPagination(25, 0);
        $this->assertSame('OFFSET 0 ROWS FETCH NEXT 25 ROWS ONLY', $sqlsrvPage1);

        $mySqlPage3 = $mySqlSelect->buildPagination(100, 200);
        $this->assertSame('LIMIT 100 OFFSET 200', $mySqlPage3);

        $sqlsrvPage3 = $sqlsrvSelect->buildPagination(100, 200);
        $this->assertSame('OFFSET 200 ROWS FETCH NEXT 100 ROWS ONLY', $sqlsrvPage3);
    }

    public function testEscapedQuery()
    {
        $query = "SELECT * FROM MyTable";
        $selector = new Selector($query, new \PeachySQL\Mysql\Options());
        $result = $selector
            ->where(['MyCol' => 'test'], true)
            ->orderBy(['MyCol' => 'desc'], true)
            ->paginate(1, 25)
            ->getSqlParams();

        $expected = "{$query} WHERE `MyCol` = ? ORDER BY `MyCol` DESC LIMIT 25 OFFSET 0";
        $this->assertSame($expected, $result->getSql());
        $this->assertSame(['test'], $result->getParams());
    }

    public function testUnescapedQuery()
    {
        $query = "SELECT * FROM MyTable a INNER JOIN AnotherTable b ON b.id = a.id";
        $selector = new Selector($query, new \PeachySQL\SqlServer\Options());
        $result = $selector
            ->where(['a.id' => 1])
            ->orderBy(['a.username'])
            ->paginate(2, 25)
            ->getSqlParams();

        $expected = "{$query} WHERE a.id = ? ORDER BY a.username OFFSET 25 ROWS FETCH NEXT 25 ROWS ONLY";
        $this->assertSame($expected, $result->getSql());
        $this->assertSame([1], $result->getParams());
    }
}
