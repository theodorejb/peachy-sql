<?php

declare(strict_types=1);

namespace PeachySQL\Test;

use Exception;
use mysqli;
use Psalm\Config;

/**
 * @psalm-type MysqlConfig array{host: string, username: string, password: string, database: string}
 * @psalm-type SqlsrvConfig array{serverName: string, connectionInfo: array}
 * @psalm-type DbConfig array{mysql: MysqlConfig, sqlsrv: SqlsrvConfig}
 * @psalm-type Config array{testWith: array<string, bool>, db: DbConfig}
 */
class DbConnector
{
    private static ?mysqli $mysqlConn = null;

    /**
     * @var resource|null
     */
    private static $sqlsrvConn;

    /**
     * DB config settings
     * @var Config
     */
    private static $config;

    /**
     * @param Config $config
     */
    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * @return Config
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    public static function getMysqlConn(): mysqli
    {
        if (!self::$mysqlConn) {
            $mysql = self::$config['db']['mysql'];
            $dbPort = getenv('DB_PORT');

            if ($dbPort === false) {
                $dbPort = 3306;
            } else {
                $dbPort = (int) $dbPort;
            }

            self::$mysqlConn = new mysqli($mysql['host'], $mysql['username'], $mysql['password'], $mysql['database'], $dbPort);

            if (self::$mysqlConn->connect_errno) {
                throw new Exception('Failed to connect to MySQL: (' . self::$mysqlConn->connect_errno . ') ' . self::$mysqlConn->connect_error);
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
            $sqlsrv = self::$config['db']['sqlsrv'];
            self::$sqlsrvConn = sqlsrv_connect($sqlsrv['serverName'], $sqlsrv['connectionInfo']);

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
