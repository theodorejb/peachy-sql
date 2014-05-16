<?php

namespace PeachySQL;

/**
 * Implements the standard PeachySQL features for T-SQL (using SQLSRV extension)
 * 
 * @author Theodore Brown <https://github.com/theodorejb>
 */
class TSQL extends PeachySQL {

    /**
     * Option key for specifying the table's ID column (used to retrieve insert IDs)
     */
    const OPT_IDCOL = "idCol";

    /**
     * A SQLSRV connection resource
     * @var resource
     */
    private $connection;

    /**
     * Default T-SQL-specific options
     * @var array
     */
    private $tsqlOptions = [
        self::OPT_IDCOL => NULL,
    ];

    /**
     * @param resource $connection A SQLSRV connection resource
     * @param array $options Options used when querying the database
     */
    public function __construct($connection, array $options = []) {
        $this->setConnection($connection);
        $this->setOptions($options);
    }

    /**
     * Easily switch to a different SQL Server database connection
     * @param resource $connection
     */
    public function setConnection($connection) {
        if (!is_resource($connection) || get_resource_type($connection) !== 'SQL Server Connection') {
            throw new \InvalidArgumentException('Connection must be a SQL Server Connection resource');
        }

        $this->connection = $connection;
    }

    /**
     * Set options used to select, insert, update, and delete from the database
     * @param array $options
     */
    public function setOptions(array $options) {
        $this->options = array_merge($this->tsqlOptions, $this->options);
        parent::setOptions($options);
    }

    /**
     * Begins a SQLSRV transaction
     * @throws SQLException if an error occurs
     */
    public function begin() {
        if (!sqlsrv_begin_transaction($this->connection)) {
            throw new SQLException("Failed to begin transaction", sqlsrv_errors());
        }
    }

    /**
     * Commits a transaction begun with begin()
     * @throws SQLException if an error occurs
     */
    public function commit() {
        if (!sqlsrv_commit($this->connection)) {
            throw new SQLException("Failed to commit transaction", sqlsrv_errors());
        }
    }

    /**
     * Rolls back a transaction begun with begin()
     * @throws SQLException if an error occurs
     */
    public function rollback() {
        if (!sqlsrv_rollback($this->connection)) {
            throw new SQLException("Failed to roll back transaction", sqlsrv_errors());
        }
    }

    /**
     * Executes a query and passes a SQLResult object to the callback.
     * @param string   $sql
     * @param array    $params   Values to bind to placeholders in the query
     * @param callable $callback
     * @return mixed A SQLResult object, or the return value of the specified callback
     * @throws SQLException if an error occurs
     */
    public function query($sql, array $params = [], callable $callback = NULL) {
        if ($callback === NULL) {
            $callback = function (SQLResult $result) {
                return $result;
            };
        }

        if (!$stmt = sqlsrv_query($this->connection, $sql, $params)) {
            throw new SQLException("Query failed", sqlsrv_errors(), $sql, $params);
        }

        $rows = [];
        $affected = 0;

        do {
            // get any selected rows
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }

            $affectedRows = sqlsrv_rows_affected($stmt);

            if ($affectedRows > 0) {
                $affected += $affectedRows;
            }
        } while ($nextResult = sqlsrv_next_result($stmt));

        if ($nextResult === FALSE) {
            throw new SQLException("Failed to get next result", sqlsrv_errors(), $sql, $params);
        }

        sqlsrv_free_stmt($stmt);

        return $callback(new SQLResult($rows, $affected, $sql));
    }

    /**
     * Inserts one or more rows into the table. If multiple rows are inserted 
     * (via nested arrays) an array of insert IDs will be passed to the callback. 
     * If inserting a single row with a flat array of values the insert ID will 
     * instead be passed as an integer. Returns the return value of the callback.
     * 
     * @param string[] $columns  The columns to be inserted into. E.g. ["Username", "Password"].
     * @param array    $values   A flat array of values (to insert one row), or an array containing 
     *                           one or more subarrays (to bulk-insert multiple rows).
     *                           E.g. ["user", "pass"] or [ ["user1", "pass1"], ["user2", "pass2"] ].
     * @param callable $callback function (array|int $insertIds, SQLResult $result)
     */
    public function insert(array $columns, array $values, callable $callback = NULL) {
        if ($callback === NULL) {
            $callback = function ($ids) {
                return $ids;
            };
        }

        $query = self::buildInsertQuery($this->options[self::OPT_TABLE], $columns, $values, $this->options[self::OPT_IDCOL]);
        $bulkInsert = $query['isBulk'];

        return $this->query($query["sql"], $query["params"], function (SQLResult $result) use ($bulkInsert, $callback) {
            $ids = $bulkInsert ? [] : 0;
            $rows = $result->getRows(); // contains any insert IDs

            if (isset($rows[0])) {
                // $rows contains an array of insert ID rows

                if ($bulkInsert) {
                    foreach ($rows as $row) {
                        $ids[] = $row["RowID"];
                    }
                } else {
                    $ids = $rows[0]["RowID"];
                }
            }

            return $callback($ids, $result);
        });
    }

    /**
     * Generates an INSERT query with placeholders for values and optional OUTPUT clause
     * @param string $tableName
     * @param array  $columns
     * @param array  $values
     * @param string $idCol
     * @return array
     */
    public static function buildInsertQuery($tableName, array $columns, array $values, $idCol = NULL) {
        $comp = self::buildInsertQueryComponents($tableName, $columns, $values);

        if ($idCol !== NULL && $idCol !== '') {
            $decStr = "DECLARE @ids TABLE(RowID int); ";
            $outStr = " OUTPUT inserted.$idCol INTO @ids(RowID)";
            $selStr = "; SELECT * FROM @ids;";
        } else {
            $decStr = '';
            $outStr = '';
            $selStr = '';
        }

        $comp['sql'] = $decStr . $comp['insertStr'] . $outStr . $comp['valStr'] . $selStr;
        return $comp;
    }

}
