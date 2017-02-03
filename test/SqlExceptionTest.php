<?php

declare(strict_types=1);

namespace PeachySQL;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the SqlException object
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class SqlExceptionTest extends TestCase
{
    public function exceptionProvider(): array
    {
        $errCode = 1193;
        $sqlState = 'HY000';
        $message = "Unknown system variable 'a'";
        $expectedMsg = "Error: $message";

        $mysql = [
            [
                'errno' => $errCode,
                'sqlstate' => $sqlState,
                'error' => $message,
            ]
        ];

        $sqlServer = [
            [
                'code' => $errCode,
                'SQLSTATE' => $sqlState,
                'message' => $message,
            ]
        ];

        return [
            [new SqlException('Error', $mysql), $expectedMsg, $sqlState, $errCode],
            [new SqlException('Error', $sqlServer), $expectedMsg, $sqlState, $errCode],
        ];
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testDataAccess(SqlException $e, string $expectedMsg, string $sqlState, int $errCode)
    {
        $this->assertSame($expectedMsg, $e->getMessage());
        $this->assertSame($sqlState, $e->getSqlState());
        $this->assertSame($errCode, $e->getCode());
    }
}
