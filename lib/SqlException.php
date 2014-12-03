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
     * @param string     $msg    The error message
     * @param array      $errors An error array returned by sqlsrv_errors() or mysqli::$error_list)
     * @param string     $query  The failed query
     * @param array      $params Any parameters bound to the failed query
     * @param \Exception $prev   Optionally set a previous exception
     * @param int        $code   Optionally set an error code
     */
    public function __construct($msg, array $errors, $query = null, array $params = null, \Exception $prev = null, $code = 0)
    {
        parent::__construct($msg, $code, $prev);

        $this->errors = $errors;
        $this->query = $query;
        $this->params = $params;
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
