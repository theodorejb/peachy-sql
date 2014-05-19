# PeachySQL

[![Packagist version](https://img.shields.io/packagist/v/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![Total downloads](https://img.shields.io/packagist/dt/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql) [![License](https://img.shields.io/packagist/l/theodorejb/peachy-sql.svg)](https://packagist.org/packages/theodorejb/peachy-sql)

PeachySQL is a speedy database abstraction layer with a goal of simplifying the experience of performing common SQL queries in PHP. It currently supports both MySQL (via MySQLi) and T-SQL (via Microsoft's [SQLSRV extension](http://www.php.net/manual/en/book.sqlsrv.php)) and runs on PHP 5.4+.

## Installation

To install via [Composer](https://getcomposer.org/), add the following to the composer.json file in your project root:

```json
{
    "require": {
        "theodorejb/peachy-sql": "2.x"
    }
}
```

Then run `composer install` and require `vendor/autoload.php` in your application's index.php file.

## Usage

Start by instantiating the MySQL or TSQL class with a database connection, which should be an existing [mysqli object](http://www.php.net/manual/en/mysqli.construct.php) or [SQLSRV connection resource](http://www.php.net/manual/en/function.sqlsrv-connect.php):

```php
$peachySql = new \PeachySQL\MySQL($mysqlConn);
```
or
```php
$peachySql = new \PeachySQL\TSQL($tsqlConn);
```

After instantiation, arbitrary queries can be executed by passing a SQL string and array of bound parameters to the `query()` method:

```php
$sql = 'SELECT * FROM Users WHERE fname LIKE ? AND lname LIKE ?';
$result = $peachySql->query($sql, ['theo%', 'b%']);
echo json_encode($result->getRows());
```

The `query()` method returns a `SQLResult` object, and in addition to `getRows()`, this object has `getAffected()` and `getQuery()` methods (to return the number of affected rows and the executed query string, respectively). If using the `MySQL` class, `query()` will return a `MySQLResult` subclass, which adds a `getFirstInsertId()` method to those mentioned above. 

### Shorthand methods

When creating an instance of PeachySQL, an "options" argument can be passed specifying a table name and (for `TSQL` instances) its identity column:

```php
$options = [
    'table' => 'Users',
    'idCol' => 'user_id' // used to retrive insert IDs when using T-SQL
];

$userTable = new \PeachySQL\TSQL($conn, $options);
```
or for MySQL:
```php
$userTable = new \PeachySQL\MySQL($conn, ['table' => 'Users']);
```

You can then make use of PeachySQL's five shorthand methods: `select()`, `insert()`,  `insertAssoc()`, `update()`, and `delete()`. For example:

```php
// select first and last name columns where user_id is equal to 5
$rows = $userTable->select(['fname', 'lname'], ['user_id' => 5]);

// bulk-insert 3 rows into the Users table
$userData = [
    ['Theodore', 'Brown'],
    ['Grace',    'Hopper'],
    ['Douglas',  'Engelbart']
];

// $ids will contain an array of the values inserted 
// into the auto-incremented user_id column.
$ids = $userTable->insert(['fname', 'lname'], $userData);

// insert a single row from an associative array
$userData = ['fname' => 'Donald', 'lname' => 'Chamberlin'];
$id = $userTable->insertAssoc($userData);

// update the user with user_id 4
$newData = ['fname' => 'Raymond', 'lname' => 'Boyce'];
$userTable->update($newData, ['user_id' => 4]);

// delete users with IDs 1, 2, and 3
$userTable->delete(['user_id' => [1, 2, 3]]);
```

Each of the shorthand methods accepts an optional callback argument as their last parameter which is passed a SQLResult object.

**Note:** To prevent SQL injection, the shorthand methods always generate prepared statements with bound parameters for inserted/queried values. However, since table and column names cannot be bound they should never be set dynamically from user input without careful validation/sanitization.

### Transactions

Call the `begin()` method to start a transaction. You can then call `query()` and any of the shorthand methods as needed, before committing or rolling back the transaction with `commit()` or `rollback()`.

### Other methods

The database connection can be swapped out at any time with `setConnection()`, and `setOptions()` and `getOptions()` methods allow PeachySQL options to be changed and retrieved at will. 

In addition to the "table" and T-SQL-specific "idCol" options mentioned above, there is a MySQL-specific "autoIncrementIncrement" option which can be used to set the interval between successive auto-incremented values in the table (defaults to 1). This option is used to determine the array of insert IDs for bulk-inserts, since MySQL only provides the first insert ID.

## Contributing

Bug reports and pull requests are welcome! After editing files, open a command prompt in the working directory and run `phpunit` to ensure that all the tests pass.

## Author

Theodore Brown  
[@theodorejb](https://twitter.com/theodorejb)  
<http://designedbytheo.com>

## License

MIT
