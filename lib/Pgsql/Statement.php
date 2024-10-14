<?php

declare(strict_types=1);

namespace PeachySQL\Pgsql;

use PeachySQL\BaseStatement;
use PeachySQL\Pgsql;
use PDO;
use PDOException;
use PDOStatement;

class Statement extends BaseStatement
{
    private ?PDOStatement $stmt;

    public function __construct(PDOStatement $stmt, bool $usedPrepare)
    {
        parent::__construct($usedPrepare);
        $this->stmt = $stmt;
    }

    public function execute(): void
    {
        if ($this->stmt === null) {
            throw new \Exception('Cannot execute closed statement');
        }

        try {
            if (!$this->stmt->execute()) {
                throw Pgsql::getError('Failed to execute prepared statement', $this->stmt->errorInfo());
            }
        } catch (PDOException $e) {
            throw Pgsql::getError('Failed to execute prepared statement', $this->stmt->errorInfo());
        }

        $this->affected = $this->stmt->rowCount();

        if (!$this->usedPrepare && $this->stmt->columnCount() === 0) {
            $this->close(); // no results, so statement can be closed
        }
    }

    public function getIterator(): \Generator
    {
        if ($this->stmt !== null) {
            while (
                /** @var array|false $row */
                $row = $this->stmt->fetch(PDO::FETCH_ASSOC)
            ) {
                yield $row;
            }

            if (!$this->usedPrepare) {
                $this->close();
            }
        }
    }

    /**
     * Closes the prepared statement and deallocates the statement handle.
     * @throws \Exception if the statement has already been closed
     */
    public function close(): void
    {
        if ($this->stmt === null) {
            throw new \Exception('Statement has already been closed');
        }

        $this->stmt->closeCursor();
        $this->stmt = null;
    }
}
