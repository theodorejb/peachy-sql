<?php

namespace PeachySQL;

/**
 * Has methods to retrieve the SQL query, bound parameters, and error array
 * (returned by sqlsrv_errors() or mysqli::$error_list).
 */
class SqlException extends \Exception
{
    private $errors;
    private $query;
    private $params;

    /**
     * @param string $msg The error message
     * @param array $errors An error array returned by sqlsrv_errors() or mysqli::$error_list)
     * @param string $query The failed query
     * @param array $params Any parameters bound to the failed query
     */
    public function __construct($msg, array $errors, $query = null, array $params = null)
    {
        if (isset($errors[0]['error'])) {
            $msg .= ': ' . $errors[0]['error']; // MySQL
        } elseif (isset($errors[0]['message'])) {
            $msg .= ': ' . $errors[0]['message']; // SQL Server
        }

        if (isset($errors[0]['errno'])) {
            $code = $errors[0]['errno']; // MySQL
        } elseif (isset($errors[0]['code'])) {
            $code = $errors[0]['code']; // SQL Server
        } else {
            $code = 0;
        }

        parent::__construct($msg, $code);

        $this->errors = $errors;
        $this->query = $query;
        $this->params = $params;
    }

    /**
     * @return string The SQLSTATE error for the exception
     */
    public function getSqlState()
    {
        if (isset($this->errors[0]['sqlstate'])) {
            return $this->errors[0]['sqlstate']; // MySQL
        } elseif (isset($this->errors[0]['SQLSTATE'])) {
            return $this->errors[0]['SQLSTATE']; // SQL Server
        } else {
            return null;
        }
    }

    /**
     * Returns the list of errors from sqlsrv_errors() or mysqli::$error_list
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns the failed SQL query
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Returns the array of bound parameters
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
