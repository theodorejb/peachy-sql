<?php

namespace PeachySQL\QueryBuilder;

/**
 * Tests for the Select query builder
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class SelectTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildQueryAllRows()
    {
        $options = new \PeachySQL\Mysql\Options();
        $options->setTable('TestTable');
        $actual = (new Select($options))->buildQuery();
        $expected = 'SELECT * FROM TestTable';
        $this->assertSame($expected, $actual->getSql());
    }

    public function testBuildQueryWhere()
    {
        $options = new \PeachySQL\Mysql\Options();
        $options->setTable('TestTable');

        $where = [
            'username' => 'TestUser',
            'password' => 'TestPassword',
            'othercol' => null
        ];

        $actual = (new Select($options))->buildQuery(['username', 'password'], $where);
        $expected = 'SELECT `username`, `password` FROM TestTable WHERE '
            . '`username` = ? AND `password` = ? AND `othercol` IS NULL';
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame(['TestUser', 'TestPassword'], $actual->getParams());
    }

    public function testBuildQueryOrderBy()
    {
        $options = new \PeachySQL\SqlServer\Options();
        $options->setTable('TestTable');
        $cols = ['username', 'lastname'];
        $where = [];
        $orderBy = ['lastname', 'firstname'];

        $actual = (new Select($options))->buildQuery($cols, $where, $orderBy);
        $expected = 'SELECT [username], [lastname] FROM TestTable ORDER BY [lastname], [firstname]';
        $this->assertSame($expected, $actual->getSql());
        $this->assertSame([], $actual->getParams());
    }
}
