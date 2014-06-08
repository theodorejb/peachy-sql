<?php

namespace PeachySQL;

class TestDbConnector
{
    /**
     * @var mysqli
     */
    private static $mysqlConn;

    /**
     * @var resource
     */
    private static $sqlsrvConn;

    public static function getMysqlConn()
    {
        if (!self::$mysqlConn) {
            self::$mysqlConn = new \mysqli('127.0.0.1', 'root', '', 'PeachySQL');
            if (self::$mysqlConn->connect_errno) {
                throw new \Exception("Failed to connect to MySQL: (" . self::$mysqlConn->connect_errno . ") " . self::$mysqlConn->connect_error);
            }

            self::createMysqlTestTable(self::$mysqlConn);
        }

        return self::$mysqlConn;
    }

    public static function getSqlsrvConn()
    {
        if (!self::$sqlsrvConn) {
            $connInfo = ["Database" => 'PeachySQL', "ReturnDatesAsStrings" => true];
            self::$sqlsrvConn = sqlsrv_connect('Computer-Name\SQLEXPRESS', $connInfo);
            if (!self::$sqlsrvConn) {
                throw new \Exception("Failed to connect to SQL server: " . print_r(sqlsrv_errors(), true));
            }

            self::createTsqlTestTable(self::$sqlsrvConn);
        }

        return self::$sqlsrvConn;
    }

    private static function createTsqlTestTable($conn)
    {
        $sql = "CREATE TABLE Users (
                    user_id INT PRIMARY KEY IDENTITY(1,1) NOT NULL,
                    fname VARCHAR(20) NOT NULL,
                    lname VARCHAR(20) NOT NULL,
                    dob DATE NOT NULL
                );";

        if (!sqlsrv_query($conn, $sql)) {
            throw new \Exception("Failed to create T-SQL test table: " . print_r(sqlsrv_errors(), true));
        }
    }

    private static function createMysqlTestTable(\mysqli $conn)
    {
        $sql = "CREATE TABLE Users (
                    user_id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                    fname VARCHAR(20) NOT NULL,
                    lname VARCHAR(20) NOT NULL,
                    dob DATE NOT NULL
                );";

        if (!$conn->query($sql)) {
            throw new \Exception("Failed to create MySQL test table: " . print_r($conn->error_list, true));
        }
    }

    public static function deleteTestTables()
    {
        $sql = "DROP TABLE Users";

        if (self::$mysqlConn) {
            if (!self::$mysqlConn->query($sql)) {
                throw new \Exception("Failed to drop MySQL test table: " . print_r(self::$mysqlConn->error_list, true));
            }
        }

        if (self::$sqlsrvConn) {
            if (!sqlsrv_query(self::$sqlsrvConn, $sql)) {
                throw new \Exception("Failed to drop T-SQL test table: " . print_r(sqlsrv_errors(), true));
            }
        }
    }
}
