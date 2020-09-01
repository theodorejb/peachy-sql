<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Has methods to retrieve the SQL query, bound parameters, and error array
 * (returned by sqlsrv_errors() or mysqli::$error_list).
 */
class SqlException extends \RuntimeException
{
    /** @var array */
    private $errors;
    /** @var string */
    private $query;
    /** @var array */
    private $params;

    /**
     * @param string $msg The error message
     * @param array $errors An error array returned by sqlsrv_errors() or mysqli::$error_list)
     * @param string $query The failed query
     * @param array $params Any parameters bound to the failed query
     */
    public function __construct(string $msg, array $errors, string $query = '', array $params = [])
    {

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
        //                       MySQL                        SQL Server
        return $this->errors[0]['sqlstate'] ?? $this->errors[0]['SQLSTATE'] ?? '';
    }

    /**
     * Returns the list of errors from sqlsrv_errors() or mysqli::$error_list
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns the failed SQL query
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Returns the array of bound parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
