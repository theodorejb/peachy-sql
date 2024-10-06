<?php

declare(strict_types=1);

namespace PeachySQL\Test;

use PeachySQL\SqlException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the SqlException object
 */
class SqlExceptionTest extends TestCase
{
    public function testDataAccess(): void
    {
        $errCode = 1193;
        $sqlState = 'HY000';
        $message = "Unknown system variable 'a'";
        $expectedMsg = "Error: $message";

        $mysqlException = new SqlException('Error', [
            [
                'errno' => $errCode,
                'sqlstate' => $sqlState,
                'error' => $message,
            ]
        ]);

        $this->assertSame($expectedMsg, $mysqlException->getMessage());
        $this->assertSame($sqlState, $mysqlException->getSqlState());
        $this->assertSame($errCode, $mysqlException->getCode());

        $sqlsrvException = new SqlException('Error', [
            [
                'code' => $errCode,
                'SQLSTATE' => $sqlState,
                'message' => $message,
            ]
        ]);

        $this->assertSame($expectedMsg, $sqlsrvException->getMessage());
        $this->assertSame($sqlState, $sqlsrvException->getSqlState());
        $this->assertSame($errCode, $sqlsrvException->getCode());
    }
}
