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

    /**
     * DB config settings
     * @var array
     */
    private static $config;

    public static function setConfig(array $config)
    {
        self::$config = $config;
    }

    public static function getConfig()
    {
        return self::$config;
    }

    public static function getMysqlConn()
    {
        if (!self::$mysqlConn) {
            $mysql = self::$config['db']['mysql'];
            self::$mysqlConn = new \mysqli($mysql['host'], $mysql['username'], $mysql['password'], $mysql['database']);

            if (self::$mysqlConn->connect_errno) {
                throw new \Exception('Failed to connect to MySQL: (' . self::$mysqlConn->connect_errno . ') ' . self::$mysqlConn->connect_error);
            }

            self::createMysqlTestTable(self::$mysqlConn);
        }

        return self::$mysqlConn;
    }

    public static function getSqlsrvConn()
    {
        if (!self::$sqlsrvConn) {
            $connInfo = self::$config['db']['sqlsrv']['connectionInfo'];
            self::$sqlsrvConn = sqlsrv_connect(self::$config['db']['sqlsrv']['serverName'], $connInfo);
            if (!self::$sqlsrvConn) {
                throw new \Exception('Failed to connect to SQL server: ' . print_r(sqlsrv_errors(), true));
            }

            self::createSqlServerTestTable(self::$sqlsrvConn);
        }

        return self::$sqlsrvConn;
    }

    private static function createSqlServerTestTable($conn)
    {
        $sql = 'CREATE TABLE Users (
                    user_id INT PRIMARY KEY IDENTITY(1,1) NOT NULL,
                    fname VARCHAR(20) NOT NULL,
                    lname VARCHAR(20) NOT NULL,
                    dob DATE NOT NULL
                );';

        if (!sqlsrv_query($conn, $sql)) {
            throw new \Exception('Failed to create SQL Server test table: ' . print_r(sqlsrv_errors(), true));
        }
    }

    private static function createMysqlTestTable(\mysqli $conn)
    {
        $sql = 'CREATE TABLE Users (
                    user_id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                    fname VARCHAR(20) NOT NULL,
                    lname VARCHAR(20) NOT NULL,
                    dob DATE NOT NULL
                );';

        if (!$conn->query($sql)) {
            throw new \Exception('Failed to create MySQL test table: ' . print_r($conn->error_list, true));
        }
    }

    public static function deleteTestTables()
    {
        $sql = 'DROP TABLE Users';

        if (self::$mysqlConn) {
            if (!self::$mysqlConn->query($sql)) {
                throw new \Exception('Failed to drop MySQL test table: ' . print_r(self::$mysqlConn->error_list, true));
            }
        }

        if (self::$sqlsrvConn) {
            if (!sqlsrv_query(self::$sqlsrvConn, $sql)) {
                throw new \Exception('Failed to drop SQL Server test table: ' . print_r(sqlsrv_errors(), true));
            }
        }
    }
}
