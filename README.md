# PeachySQL

PeachySQL is a small and speedy database abstraction layer with a goal of simplifying the experience of working with common SQL queries in PHP. It currently supports both MySQL and T-SQL (via Microsoft's [SQLSRV extension](http://www.php.net/manual/en/book.sqlsrv.php)) and runs on PHP 5.4+.

## Installation

To install via [Composer](https://getcomposer.org/), add the following to the composer.json file in your project root:

```json
{
    "require": {
        "theodorejb/peachy-sql": "1.x"
    }
}
```

Then run `composer install` and require `vendor/autoload.php` in your application's index.php file. Alternatively, PeachySQL can be installed manually by downloading `lib/PeachySQL.php` and requiring it prior to use.

## Usage

Start by instantiating the class with a database connection (mysqli or SQLSRV), the database type ('mysql' or 'tsql'), and the name of the table to query (optional):

```php
$userTable = new PeachySQL($conn, 'mysql', 'Users');
```

After instantiation, arbitrary SQL queries can be executed using the `query()` method, which accepts a SQL string, list of bound parameters, and a callback function (see PHPDoc comments for details):

```php
$userTable->query('SELECT * FROM Users WHERE name LIKE ?', ['test%'], function ($err, $rows) {
    if ($err) {
        throw new Exception("Failed to select rows: " . print_r($err, TRUE));
    }

    print_r($rows);
});
```

If PeachySQL is instantiated with a table name, you can make use of its handy `select()`, `insert()`, `update()`, and `delete()` methods. For example:

```php
// select all columns where user_id is equal to 5
$userTable->select([], ['user_id' => 5], function ($err, $rows) {
    if ($err) {
        throw new Exception("Failed to select user: " . print_r($err, TRUE));
    }

    print_r($rows);
});

// insert 3 rows into the Users table
$userData = array(
    array('user1', 21),
    array('user2', 55),
    array('user3', 30)
);

$userTable->insert(['name', 'age'], $userData, 'user_id', function ($err, $ids) {
    if ($err) {
        throw new Exception("Failed to insert users: " . print_r($err, TRUE));
    }

    // $ids contains an array of the values inserted 
    // into the auto-incremented user_id column.
    print_r($ids);
});

// update the user with user_id 10
$newData = array(
    'name' => 'Matt',
    'age'  => 29
);

$userTable->update($newData, ['user_id' => 10], function ($err, $rows, $affected) {
    if ($err) {
        throw new Exception("Failed to update user: " . print_r($err, TRUE));
    }

    echo $affected . " rows were affected."; // 1 rows were affected
});

// delete users with IDs 7, 8, and 9
$userTable->delete(['user_id' => [7, 8, 9]], function ($err, $rows, $affected) {
    if ($err) {
        throw new Exception("Failed to delete users: " . print_r($err, TRUE));
    }

    echo $affected . " rows were affected."; // 3 rows were affected
}); 
```

## Development

After editing files, open a command prompt in the working directory and run `phpunit` to ensure that all the tests pass. Bug reports and pull requests are welcome!

## Author

Theodore Brown  
[@theodorejb](https://twitter.com/theodorejb)  
<http://designedbytheo.com>

## License

MIT
