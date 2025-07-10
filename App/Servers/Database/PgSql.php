<?php
/**
 * Handling Database via pgsql
 * php version 8.3
 *
 * @category  Database
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
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
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PgSql extends AbstractDatabase
{
    /**
     * Database hostname
     *
     * @var null|string
     */
    private $_hostname = null;

    /**
     * Database port
     *
     * @var null|string
     */
    private $_port = null;

    /**
     * Database username
     *
     * @var null|string
     */
    private $_username = null;

    /**
     * Database password
     *
     * @var null|string
     */
    private $_password = null;

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
    private $_db = null;

    /**
     * Executed query statement
     *
     * @var null|\PgSql\Result
     */
    private $_stmt = null;

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
        $this->_hostname = $hostname;
        $this->_port = $port;
        $this->_username = $username;
        $this->_password = $password;

        if (!is_null(value: $database)) {
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
        if (!is_null(value: $this->_db)) {
            return;
        }

        try {
            $this->_db = pg_connect(
                "host={$this->_hostname} \
                port={$this->_port} \
                user={$this->_username} \
                password={$this->_password}"
            );

            if (!is_null(value: $this->database)) {
                $this->useDatabase();
            }
        } catch (\Exception $e) {
            $this->_log(e: $e);
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
            if (!is_null(value: $this->database)) {
                pg_query($this->_db, "set schema '{$this->database}';");
            }
        } catch (\Exception $e) {
            $this->rollback();
            $this->_log(e: $e);
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
            pg_query($this->_db, 'BEGIN');
        } catch (\Exception $e) {
            $this->_log(e: $e);
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
                pg_query($this->_db, 'COMMIT');
            }
        } catch (\Exception $e) {
            $this->_log(e: $e);
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
                pg_query($this->_db, 'ROLLBACK');
            }
        } catch (\Exception $e) {
            $this->_log(e: $e);
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
            if ($this->_stmt) {
                return (int)pg_affected_rows($this->_stmt);
            }
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->_log(e: $e);
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
            $this->_log(e: $e);
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
            pg_prepare($this->_db, 'pg_query', $sql);
            pg_execute($this->_db, 'pg_query', $params);
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->_log(e: $e);
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
            if ($this->_stmt) {
                return pg_fetch_assoc($this->_stmt);
            }
        } catch (\Exception $e) {
            $this->_log(e: $e);
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
            if ($this->_stmt) {
                return pg_fetch_all($this->_stmt);
            }
        } catch (\Exception $e) {
            $this->_log(e: $e);
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
            if ($this->_stmt) {
                pg_free_result($this->_stmt);
            }
            if ($this->_db) {
                pg_flush($this->_db);
            }
        } catch (\Exception $e) {
            $this->_log(e: $e);
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
    private function _log($e): never
    {
        throw new \Exception(
            message: pg_last_error($this->_db),
            code: HttpStatus::$InternalServerError
        );
    }
}
