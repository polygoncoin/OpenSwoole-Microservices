<?php

/**
 * Handling Database via pgsql
 * php version 8.3
 *
 * @category  Database
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Servers\Database;

use Microservices\App\HttpStatus;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * PgSQL Database
 * php version 8.3
 *
 * @category  Database_PgSQL
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PgSql extends AbstractDatabase
{
    /**
     * Database hostname
     *
     * @var null|string
     */
    private $hostname = null;

    /**
     * Database port
     *
     * @var null|string
     */
    private $port = null;

    /**
     * Database username
     *
     * @var null|string
     */
    private $username = null;

    /**
     * Database password
     *
     * @var null|string
     */
    private $password = null;

    /**
     * Database database
     *
     * @var null|string
     */
    public $database = null;

    /**
     * Database connection
     *
     * @var null|\PgSql\Connection
     */
    private $db = null;

    /**
     * Executed query statement
     *
     * @var null|\PgSql\Result
     */
    private $stmt = null;

    /**
     * Transaction started flag
     *
     * @var bool
     */
    public $beganTransaction = false;

    /**
     * Database constructor
     *
     * @param string      $hostname Hostname .env string
     * @param string      $port     Port .env string
     * @param string      $username Username .env string
     * @param string      $password Password .env string
     * @param null|string $database Database .env string
     */
    public function __construct(
        $hostname,
        $port,
        $username,
        $password,
        $database = null
    ) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        if ($database !== null) {
            $this->database = $database;
        }
    }

    /**
     * Database connection
     *
     * @return void
     */
    public function connect(): void
    {
        if ($this->db !== null) {
            return;
        }

        try {
            $this->db = pg_connect(
                "host={$this->hostname} \
                port={$this->port} \
                user={$this->username} \
                password={$this->password}"
            );

            if ($this->database !== null) {
                $this->useDatabase();
            }
        } catch (\Exception $e) {
            $this->log(e: $e);
        }
    }

    /**
     * Use Database
     *
     * @return void
     */
    public function useDatabase(): void
    {
        $this->connect();

        try {
            if ($this->database !== null) {
                pg_query($this->db, "set schema '{$this->database}';");
            }
        } catch (\Exception $e) {
            $this->rollback();
            $this->log(e: $e);
        }
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    public function begin(): void
    {
        $this->useDatabase();

        $this->beganTransaction = true;
        try {
            pg_query($this->db, 'BEGIN');
        } catch (\Exception $e) {
            $this->log(e: $e);
        }
    }

    /**
     * Commit transaction
     *
     * @return void
     */
    public function commit(): void
    {
        try {
            if ($this->beganTransaction) {
                $this->beganTransaction = false;
                pg_query($this->db, 'COMMIT');
            }
        } catch (\Exception $e) {
            $this->log(e: $e);
        }
    }

    /**
     * Rollback transaction
     *
     * @return void
     */
    public function rollback(): void
    {
        try {
            if ($this->beganTransaction) {
                $this->beganTransaction = false;
                pg_query($this->db, 'ROLLBACK');
            }
        } catch (\Exception $e) {
            $this->log(e: $e);
        }
    }

    /**
     * Affected Rows by PDO
     *
     * @return bool|int
     */
    public function affectedRows(): bool|int
    {
        try {
            if ($this->stmt) {
                return (int)pg_affected_rows($this->stmt);
            }
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->log(e: $e);
        }
        return false;
    }

    /**
     * Last Insert Id by PDO
     *
     * @return bool|int
     */
    public function lastInsertId(): bool|int
    {
        try {
            $this->execDbQuery(sql: 'SELECT lastval()');
            $row = pg_fetch_row();
            if ($row[0]) {
                return $row[0];
            }
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->log(e: $e);
        }
        return false;
    }

    /**
     * Execute Parameterized query
     *
     * @param string $sql     Parameterized query
     * @param array  $params  Parameterized query params
     * @param bool   $pushPop Push Pop result set stmt
     *
     * @return void
     */
    public function execDbQuery($sql, $params = [], $pushPop = false): void
    {
        $this->useDatabase();

        try {
            pg_prepare($this->db, 'pg_query', $sql);
            pg_execute($this->db, 'pg_query', $params);
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->log(e: $e);
        }
    }

    /**
     * Fetch row from statement
     *
     * @return mixed
     */
    public function fetch(): mixed
    {
        try {
            if ($this->stmt) {
                return pg_fetch_assoc($this->stmt);
            }
        } catch (\Exception $e) {
            $this->log(e: $e);
        }
        return false;
    }

    /**
     * Fetch all rows from statement
     *
     * @return array|bool
     */
    public function fetchAll(): array|bool
    {
        try {
            if ($this->stmt) {
                return pg_fetch_all($this->stmt);
            }
        } catch (\Exception $e) {
            $this->log(e: $e);
        }
        return false;
    }

    /**
     * Close statement cursor
     *
     * @param bool $pushPop Push Pop result set stmt
     *
     * @return void
     */
    public function closeCursor($pushPop = false): void
    {
        try {
            if ($this->stmt) {
                pg_free_result($this->stmt);
            }
            if ($this->db) {
                pg_flush($this->db);
            }
        } catch (\Exception $e) {
            $this->log(e: $e);
        }
    }

    /**
     * Log error
     *
     * @param \Exception $e Exception object
     *
     * @return never
     * @throws \Exception
     */
    private function log($e): never
    {
        throw new \Exception(
            message: pg_last_error($this->db),
            code: HttpStatus::$InternalServerError
        );
    }
}
