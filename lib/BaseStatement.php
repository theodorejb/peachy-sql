<?php

namespace PeachySQL;

/**
 * Contains rows, affected count, and the query string for a completed SQL query
 */
abstract class BaseStatement
{
    protected $affected;
    protected $usedPrepare;
    protected $query;
    protected $params;

    /**
     * @param bool $usedPrepare True if the statement was created using `prepare`
     * @param string $query
     * @param array $params
     */
    public function __construct($usedPrepare, $query, array $params)
    {
        $this->usedPrepare = $usedPrepare;
        $this->query = $query;
        $this->params = $params;
    }

    /**
     * Executes the prepared statement
     * @return void
     */
    abstract public function execute();

    /**
     * Returns an iterator which can be used to loop through each row in the result
     * @return \Generator
     */
    abstract public function getIterator();

    /**
     * Closes the statement
     * @return void
     */
    abstract public function close();

    /**
     * Returns all rows selected by the query
     * @return array
     */
    public function getAll()
    {
        $rows = [];

        foreach ($this->getIterator() as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Returns the first selected row, or null if zero rows were returned
     * @return array
     */
    public function getFirst()
    {
        $iterator = $this->getIterator();

        if (defined('HHVM_VERSION')) {
            $iterator->next(); // see https://github.com/facebook/hhvm/issues/1871
        }

        $row = $iterator->current();

        if ($row !== null) {
            $this->close(); // don't leave the SQL statement open
        }

        return $row;
    }

    /**
     * Returns the number of rows affected by the query
     * @return int
     */
    public function getAffected()
    {
        return $this->affected;
    }
}
