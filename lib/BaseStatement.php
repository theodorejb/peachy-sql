<?php

declare(strict_types=1);

namespace PeachySQL;

/**
 * Contains rows, affected count, and the query string for a completed SQL query
 */
abstract class BaseStatement
{
    protected int $affected = 0;
    /**
     * True if the statement was created using `prepare()`
     */
    protected bool $usedPrepare;

    public function __construct(bool $usedPrepare)
    {
        $this->usedPrepare = $usedPrepare;
    }

    /**
     * Executes the prepared statement
     */
    abstract public function execute(): void;

    /**
     * Returns an iterator which can be used to loop through each row in the result
     * @return \Generator<int, array>
     */
    abstract public function getIterator(): \Generator;

    /**
     * Closes the statement
     */
    abstract public function close(): void;

    /**
     * Returns all rows selected by the query
     */
    public function getAll(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * Returns the first selected row, or null if zero rows were returned
     */
    public function getFirst(): ?array
    {
        $row = $this->getIterator()->current();

        if ($row !== null) {
            $this->close(); // don't leave the SQL statement open
        }

        return $row;
    }

    /**
     * Returns the number of rows affected by the query
     */
    public function getAffected(): int
    {
        return $this->affected;
    }
}
