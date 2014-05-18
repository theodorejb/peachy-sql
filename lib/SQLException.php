<?php

namespace PeachySQL;

/**
 * Has methods to retrieve the SQL query, bound parameters, and error array
 * (returned by sqlsrv_errors() or mysqli::$error_list).
 */
class SQLException extends \Exception {

    /**
     * An error array returned by sqlsrv_errors() or mysqli::$error_list)
     * @var array
     */
    private $errors;

    /**
     * The failed query
     * @var string
     */
    private $query;

    /**
     * An array of bound parameters
     * @var array
     */
    private $params;

    public function __construct($message, array $errors, $query = NULL, array $params = NULL, \Exception $previous = NULL, $code = 0) {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
        $this->query = $query;
        $this->params = $params;
    }

    /**
     * Returns the list of errors from sqlsrv_errors() or mysqli::$error_list
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Returns the failed SQL query
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * Returns the array of bound parameters
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

}
