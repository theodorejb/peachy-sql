<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Access the SQL query, bound parameters, and error list returned by the DB driver.
 */
class SqlException extends \RuntimeException
{
    /**
     * The error list returned by the database driver
     */
    public readonly array $errors;

    /**
     * The failed SQL query
     */
    public readonly string $query;

    /**
     * The failed query's bound parameters
     */
    public readonly array $params;

    /**
     * @param string $msg The error message
     * @param array $errors An error array returned by sqlsrv_errors() or mysqli::$error_list)
     * @param string $query The failed query
     * @param array $params Any parameters bound to the failed query
     */
    public function __construct(string $msg, array $errors, string $query = '', array $params = [])
    {
        /** @var array<int, array{error: string, message: string, errno: int, code: int}> $errors */

        //                     MySQL               SQL Server
        $message = $errors[0]['error'] ?? $errors[0]['message'] ?? '';

        if ($message !== '') {
            $msg .= ": {$message}";
        }

        //                  MySQL            SQL Server
        $code = $errors[0]['errno'] ?? $errors[0]['code'] ?? 0;
        parent::__construct($msg, $code);

        $this->errors = $errors;
        $this->query = $query;
        $this->params = $params;
    }

    /**
     * Returns the SQLSTATE error for the exception
     */
    public function getSqlState(): string
    {
        /** @var array<int, array{sqlstate: string, SQLSTATE: string}> $this->errors */

        //                       MySQL                        SQL Server
        return $this->errors[0]['sqlstate'] ?? $this->errors[0]['SQLSTATE'] ?? '';
    }
}
