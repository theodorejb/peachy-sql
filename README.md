# PeachySQL

[![Packagist Version](https://img.shields.io/packagist/v/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![Total Downloads](https://img.shields.io/packagist/dt/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![License](https://img.shields.io/packagist/l/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![Build Status](https://travis-ci.org/theodorejb/peachy-sql.svg?branch=master)](https://travis-ci.org/theodorejb/peachy-sql)

PeachySQL is a speedy database abstraction layer which makes it easy to execute
prepared statements and work with large amounts of data. It supports both MySQL
and SQL Server, and runs on PHP 5.5+ as well as HHVM.

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

After instantiation, arbitrary queries can be executed by passing a
SQL string and array of bound parameters to the `query` method:

```php
$sql = 'SELECT * FROM Users WHERE fname LIKE ? AND lname LIKE ?';
$result = $peachySql->query($sql, ['theo%', 'b%']);
echo json_encode($result->getAll());
```

The `SqlResult` object returned by `query` has the following methods:

Method        | Behavior
------------- | --------
`getIterator` | Returns a [Generator](http://php.net/manual/en/language.generators.overview.php) object which can be used to iterate over large result sets without caching them in memory.
`getAll`      | Returns all selected rows as an array of associative arrays.
`getFirst`    | Returns the first selected row as an associative array (or `null` if no rows were selected).
`getAffected` | Returns the number of rows affected by the query.

If using MySQL, `query` will return a `MysqlResult` subclass which adds a `getInsertId` method.

Internally, `getAll` and `getFirst` are implemented using `getIterator`.
As such they can only be called once for a given `SqlResult` object.

### Options

PeachySQL comes with five shorthand methods for selecting, inserting, updating,
and deleting records. To use these methods, a table name and list of valid
columns must be specified by passing an options object as the second argument to
the PeachySQL constructor.

```php
$options = new PeachySQL\Mysql\Options();
```
or
```php
$options = new PeachySQL\SqlServer\Options();
```

```php
$options->setTable('Users');
$options->setColumns(['user_id', 'fname', 'lname']);
```

**Note:** each of the options setter methods has a corresponding getter method
(e.g. `getColumns`) to retrieve the current setting value.

If using SQL Server, there is an additional option to set the table's identity
column. This is necessary so that PeachySQL can generate an output clause to
retrieve insert IDs.

```php
$options->setIdColumn('user_id');
$userTable = new PeachySQL\SqlServer($sqlSrvConn, $options);
```

### Shorthand methods

**Note:** to prevent SQL injection, the queries PeachySQL generates for these
methods always use bound parameters for values, and column names are validated
against the list of columns defined in the options object.

#### select

The `select` method takes three arguments, all of which are optional:

1. An array of columns to select.
2. A WHERE array to filter results.
3. An array of column names to sort by.

Selected rows are returned as an array of associative arrays,
similar to calling the `getAll` method on a `SqlResult` object for a custom query.

```php
// select all columns and rows in the table, ordered by last name and then first name
$rows = $userTable->select([], [], ['lname', 'fname']);

// select first and last name columns where user_id is equal to 5
$rows = $userTable->select(['fname', 'lname'], ['user_id' => 5]);

// select all columns for an array of user IDs
$ids = [57, 239, 31, 54, 28];
$rows = $userTable->select([], ['user_id' => $ids]);
```

#### insertOne

The `insertOne` method allows a single row to be inserted from an associative array.
It returns an `InsertResult` object with `getId` and `getAffected` methods.

```php
$userData = [
    'fname' => 'Donald',
    'lname' => 'Chamberlin'
];

$id = $userTable->insertOne($userData)->getId();
```

#### insertBulk

The `insertBulk` method makes it possible to bulk-insert multiple rows from an array.
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

$result = $userTable->insertBulk($userData);
$ids = $result->getIds(); // e.g. [64, 65, 66]
$affected = $result->getAffected(); // 3
$queries = $result->getQueryCount(); // 1
```

SQL Server allows a maximum of 1,000 rows to be inserted at a time, and limits
individual queries to 2,099 or fewer bound parameters. MySQL supports a maximum
of 65,536 bound parameters per query. These limits can be easily reached when
attempting to bulk-insert hundreds or thousands of rows at a time. To avoid
these limits, the `insertBulk` method automatically splits large bulk insert
queries into batches to efficiently handle any number of rows (`getQueryCount`
returns the number of required batches). The default limits (listed above) can
be customized via the `setMaxBoundParams` and `setMaxInsertRows` option setters.

#### update and delete

The `update` method takes two arguments: an associative array of columns/values
to update, and a WHERE array to filter which rows are updated.

The `delete` method takes a single WHERE array argument to filter the rows to delete.

Both methods return the number of affected rows.

```php
// update the user with user_id 4
$newData = ['fname' => 'Raymond', 'lname' => 'Boyce'];
$userTable->update($newData, ['user_id' => 4]);

// delete users with IDs 1, 2, and 3
$userTable->delete(['user_id' => [1, 2, 3]]);
```

### Transactions

Call the `begin` method to start a transaction. `query` and any of the shorthand
methods can then be called as needed, before committing or rolling back the
transaction with `commit` or `rollback`.

### Other methods and options

The settings object passed to PeachySQL can be retrieved at any time using the
`getOptions` method.

There is a MySQL-specific option to override the interval between successive
auto-incremented IDs in the table (defaults to 1). PeachySQL uses this value to
determine the array of insert IDs for bulk-inserts, since MySQL only provides
the first insert ID.

```php
$userTable->getOptions()->setAutoIncrementValue(2);
$userTable->insertBulk($userData)->getIds(); // e.g. [67, 69, 71]
```

## Author

Theodore Brown  
<http://theodorejb.me>

## License

MIT
