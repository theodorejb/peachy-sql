# PeachySQL

[![Packagist Version](https://img.shields.io/packagist/v/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![Total Downloads](https://img.shields.io/packagist/dt/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![License](https://img.shields.io/packagist/l/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![Build Status](https://travis-ci.org/theodorejb/peachy-sql.svg?branch=master)](https://travis-ci.org/theodorejb/peachy-sql)

PeachySQL is a speedy database abstraction layer which makes it easy to execute
prepared statements and work with large amounts of data. It supports both MySQL
and SQL Server, and runs on PHP 7.4+.

## Install via Composer

`composer require theodorejb/peachy-sql`

## Usage

Start by instantiating the `Mysql` or `SqlServer` class with a database connection,
which should be an existing [mysqli object](http://www.php.net/manual/en/mysqli.construct.php)
or [SQLSRV connection resource](http://www.php.net/manual/en/function.sqlsrv-connect.php):

```php
$peachySql = new PeachySQL\Mysql($mysqlConn);
```
or
```php
$peachySql = new PeachySQL\SqlServer($sqlSrvConn);
```

After instantiation, arbitrary statements can be prepared by passing a
SQL string and array of bound parameters to the `prepare` method:

```php
$sql = "UPDATE Users SET fname = ? WHERE user_id = ?";
$stmt = $peachySql->prepare($sql, [&$fname, &$id]);

$nameUpdates = [
    3 => 'Theodore',
    7 => 'Luke',
];

foreach ($nameUpdates as $id => $fname) {
    $stmt->execute();
}

$stmt->close();
```

Most of the time prepared statements only need to be executed a single time.
To make this easier, PeachySQL provides a `query` method which automatically
prepares, executes, and closes a statement after results are retrieved:

```php
$sql = 'SELECT * FROM Users WHERE fname LIKE ? AND lname LIKE ?';
$result = $peachySql->query($sql, ['theo%', 'b%']);
echo json_encode($result->getAll());
```

Both `prepare` and `query` return a `Statement` object with the following methods:

Method        | Behavior
------------- | --------
`execute`     | Executes the prepared statement (automatically called when using `query`).
`getIterator` | Returns a [Generator](http://php.net/manual/en/language.generators.overview.php) object which can be used to iterate over large result sets without caching them in memory.
`getAll`      | Returns all selected rows as an array of associative arrays.
`getFirst`    | Returns the first selected row as an associative array (or `null` if no rows were selected).
`getAffected` | Returns the number of rows affected by the query.
`close`       | Closes the prepared statement and frees its resources (automatically called when using `query`).

If using MySQL, the `Mysql\Statement` object additionally includes a `getInsertId` method.

Internally, `getAll` and `getFirst` are implemented using `getIterator`.
As such they can only be called once for a given statement.

### Shorthand methods

PeachySQL comes with five shorthand methods for selecting, inserting, updating,
and deleting records.

**Note:** to prevent SQL injection, the queries PeachySQL generates for these methods
always use bound parameters for values, and column names are automatically escaped.

#### selectFrom

The `selectFrom` method takes a single argument containing a SQL SELECT statement.
It returns an object with three chainable methods:

1. `where`
2. `orderBy`
3. `offset`

Additionally the object has a `getSqlParams` method which builds the select query,
and a `query` method which executes the query and returns a `Statement` object.

```php
// select all columns and rows in a table, ordered by last name and then first name
$rows = $peachySql->selectFrom("SELECT * FROM Users")
    ->orderBy(['lname', 'fname'])
    ->query()->getAll();

// select from multiple tables with conditions and pagination
$rows = $peachySql->selectFrom("SELECT * FROM Users u INNER JOIN Customers c ON c.CustomerID = u.CustomerID")
    ->where(['c.CustomerName' => 'Amazing Customer'])
    ->orderBy(['u.fname' => 'desc', 'u.lname' => 'asc'])
    ->offset(0, 50) // page 1 with 50 rows per page
    ->query()->getIterator();
```

##### Where clause generation

In addition to passing basic column => value arrays to the `where` method, you can
specify more complex conditions by using arrays as values. For example, passing
`['col' => ['lt' => 15, 'gt' => 5]]` would generate the condition `WHERE col < 15 AND col > 5`.

Full list of recognized operators:

Operator | SQL condition
-------- | -------------
eq       | =
ne       | <>
lt       | <
le       | <=
gt       | >
ge       | >=
lk       | LIKE
nl       | NOT LIKE
nu       | IS NULL
nn       | IS NOT NULL

#### insertRow

The `insertRow` method allows a single row to be inserted from an associative array.
It returns an `InsertResult` object with `getId` and `getAffected` methods.

```php
$userData = [
    'fname' => 'Donald',
    'lname' => 'Chamberlin'
];

$id = $peachySql->insertRow('Users', $userData)->getId();
```

#### insertRows

The `insertRows` method makes it possible to bulk-insert multiple rows from an array.
It returns a `BulkInsertResult` object with `getIds`, `getAffected`, and `getQueryCount` methods.

```php
$userData = [
    [
        'fname' => 'Grace',
        'lname' => 'Hopper'
    ],
    [
        'fname' => 'Douglas',
        'lname' => 'Engelbart'
    ],
    [
        'fname' => 'Margaret',
        'lname' => 'Hamilton'
    ]
];

$result = $peachySql->insertRows('Users', $userData);
$ids = $result->getIds(); // e.g. [64, 65, 66]
$affected = $result->getAffected(); // 3
$queries = $result->getQueryCount(); // 1
```

An optional third parameter can be passed to `insertRows` to override the default
identity increment value:

```php
$result = $peachySql->insertRows('Users', $userData, 2);
$ids = $result->getIds(); // e.g. [64, 66, 68]
```

Note: SQL Server allows a maximum of 1,000 rows to be inserted at a time, and limits
individual queries to 2,099 or fewer bound parameters. MySQL supports a maximum of
65,536 bound parameters per query. These limits can be easily reached when attempting
to bulk-insert hundreds or thousands of rows at a time. To avoid these limits, the
`insertRows` method automatically splits large queries into batches to efficiently
handle any number of rows (`getQueryCount` returns the number of required batches).

#### updateRows and deleteFrom

The `updateRows` method takes three arguments: a table name, an associative array of
columns/values to update, and a WHERE array to filter which rows are updated.

The `deleteFrom` method takes a table name and a WHERE array to filter the rows to delete.

Both methods return the number of affected rows.

```php
// update the user with user_id 4
$newData = ['fname' => 'Raymond', 'lname' => 'Boyce'];
$peachySql->updateRows('Users', $newData, ['user_id' => 4]);

// delete users with IDs 1, 2, and 3
$userTable->deleteFrom('Users', ['user_id' => [1, 2, 3]]);
```

### Transactions

Call the `begin` method to start a transaction. `prepare`, `execute`, `query`
and any of the shorthand methods can then be called as needed, before committing
or rolling back the transaction with `commit` or `rollback`.

## Author

Theodore Brown  
<http://theodorejb.me>

## License

MIT
