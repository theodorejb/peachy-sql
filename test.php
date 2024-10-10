<?php

use PeachySQL\Test\src\App;
use PgSql\Connection;

require 'test/bootstrap.php';

$conn = getPgsqlConn();

$sql = "INSERT INTO Users (name, dob, weight, isDisabled)
    VALUES ($1, $2, $3, $4)";

$result = pg_query_params($conn, $sql, ['George McFly', '1938-04-01', 133.8, true]);

if (!$result) {
    echo 'Failed to insert row: ' . pg_last_error($conn) . "\n";
} else {
    echo "Successfully inserted row!\n";
}

if (!pg_query($conn, "DROP TABLE Users")) {
    throw new Exception('Failed to drop PostgreSQL test table: ' . pg_last_error($conn));
} else {
    echo "Successfully dropped test table\n";
}

function getPgsqlConn(): Connection
{
    $c = App::$config;
    $connStr = "host={$c->getPgsqlHost()} dbname={$c->getPgsqlDatabase()} "
        . "user={$c->getPgsqlUser()} password={$c->getPgsqlPassword()}";
    $conn = pg_connect($connStr);

    if (!$conn) {
        throw new Exception('Failed to connect to PostgreSQL');
    }

    createPgsqlTestTable($conn);
    return $conn;
}

function createPgsqlTestTable(Connection $conn): void
{
    $sql = "CREATE TABLE Users (
                    user_id SERIAL PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    dob DATE NOT NULL,
                    weight REAL NOT NULL,
                    isDisabled BOOLEAN NOT NULL,
                    uuid bytea NULL
                )";

    if (!pg_query($conn, $sql)) {
        throw new Exception('Failed to create PostgreSQL test table: ' . pg_last_error($conn));
    }
}
