<?php

declare(strict_types=1);

namespace PeachySQL\Test\src;

use Exception;
use mysqli;

class DbConnector
{
    private static Config $config;
    private static ?mysqli $mysqlConn = null;

    /**
     * @var resource|null
     */
    private static $sqlsrvConn;

    public static function setConfig(Config $config): void
    {
        self::$config = $config;
    }

    public static function getConfig(): Config
    {
        return self::$config;
    }

    public static function getMysqlConn(): mysqli
    {
        if (!self::$mysqlConn) {
            $c = self::getConfig();
            $dbPort = getenv('DB_PORT');

            if ($dbPort === false) {
                $dbPort = 3306;
            } else {
                $dbPort = (int) $dbPort;
            }

            self::$mysqlConn = new mysqli($c->getMysqlHost(), $c->getMysqlUser(), $c->getMysqlPassword(), $c->getMysqlDatabase(), $dbPort);

            if (self::$mysqlConn->connect_error !== null) {
                throw new Exception('Failed to connect to MySQL: ' . self::$mysqlConn->connect_error);
            }

            self::createMysqlTestTable(self::$mysqlConn);
        }

        return self::$mysqlConn;
    }

    /**
     * @return resource
     */
    public static function getSqlsrvConn()
    {
        if (!self::$sqlsrvConn) {
            $c = self::getConfig();
            self::$sqlsrvConn = sqlsrv_connect($c->getSqlsrvServer(), $c->getSqlsrvConnInfo());

            if (!self::$sqlsrvConn) {
                throw new Exception('Failed to connect to SQL server: ' . print_r(sqlsrv_errors(), true));
            }

            self::createSqlServerTestTable(self::$sqlsrvConn);
        }

        return self::$sqlsrvConn;
    }

    /**
     * @param resource $conn
     */
    private static function createSqlServerTestTable($conn): void
    {
        $sql = 'CREATE TABLE Users (
                    user_id INT PRIMARY KEY IDENTITY(1,1) NOT NULL,
                    name VARCHAR(50) NOT NULL,
                    dob DATE NOT NULL,
                    weight FLOAT NOT NULL,
                    isDisabled BIT NOT NULL,
                    uuid BINARY(16) NULL
                );';

        if (!sqlsrv_query($conn, $sql)) {
            throw new Exception('Failed to create SQL Server test table: ' . print_r(sqlsrv_errors(), true));
        }
    }

    private static function createMysqlTestTable(mysqli $conn): void
    {
        $sql = 'CREATE TABLE Users (
                    user_id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                    name VARCHAR(50) NOT NULL,
                    dob DATE NOT NULL,
                    weight FLOAT NOT NULL,
                    isDisabled BOOLEAN NOT NULL,
                    uuid BINARY(16) NULL
                );';

        if (!$conn->query($sql)) {
            throw new Exception('Failed to create MySQL test table: ' . print_r($conn->error_list, true));
        }
    }

    public static function deleteTestTables(): void
    {
        $sql = 'DROP TABLE Users';

        if (self::$mysqlConn) {
            if (!self::$mysqlConn->query($sql)) {
                throw new Exception('Failed to drop MySQL test table: ' . print_r(self::$mysqlConn->error_list, true));
            }
        }

        if (self::$sqlsrvConn) {
            if (!sqlsrv_query(self::$sqlsrvConn, $sql)) {
                throw new Exception('Failed to drop SQL Server test table: ' . print_r(sqlsrv_errors(), true));
            }
        }
    }
}
