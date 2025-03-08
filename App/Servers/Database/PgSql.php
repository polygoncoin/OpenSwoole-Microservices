<?php
namespace Microservices\App\Servers\Database;

use Microservices\App\HttpStatus;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Loading database server
 *
 * This class is built to handle MySql database operation
 *
 * @category   Database - PgSql
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
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
     * @var boolean
     */
    public $beganTransaction = false;

    /**
     * Database constructor
     *
     * @param string $hostname  Hostname .env string
     * @param string $username  Username .env string
     * @param string $password  Password .env string
     * @param string $database  Database .env string
     * @return void
     */
    public function __construct(
        $hostname,
        $port,
        $username,
        $password,
        $database = null
    )
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        if (!is_null($database)) {
            $this->database = $database;
        }
    }

    /**
     * Database connection
     *
     * @return void
     */
    public function connect()
    {
        if (!is_null($this->db)) return;

        try {
            $this->db = pg_connect("host={$this->hostname} port={$this->port} user={$this->username} password={$this->password}");

            if (!is_null($this->database)) {
                $this->useDatabase();
            }
        } catch (\Exception $e) {
            $this->log($e);
        }
    }

    /**
     * Use Database
     *
     * @return void
     */
    public function useDatabase()
    {
        $this->connect();

        try {
            if (!is_null($this->database)) {
                pg_query($this->db, "set schema '{$this->database}';");
            }
        } catch (\Exception $e) {
            $this->rollback();
            $this->log($e);
        }
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    public function begin()
    {
        $this->useDatabase();

        $this->beganTransaction = true;
        try {
            pg_query($this->db, 'BEGIN');
        } catch (\Exception $e) {
            $this->log($e);
        }
    }

    /**
     * Commit transaction
     *
     * @return void
     */
    public function commit()
    {
        try {
            if ($this->beganTransaction) {
                $this->beganTransaction = false;
                pg_query($this->db, 'COMMIT');
            }
        } catch (\Exception $e) {
            $this->log($e);
        }
    }

    /**
     * Rollback transaction
     *
     * @return void
     */
    public function rollback()
    {
        try {
            if ($this->beganTransaction) {
                $this->beganTransaction = false;
                pg_query($this->db, 'ROLLBACK');
            }
        } catch (\Exception $e) {
            $this->log($e);
        }
    }

    /**
     * Affected Rows by PDO
     *
     * @return integer
     */
    public function affectedRows()
    {
        try {
            if ($this->stmt) {
                return pg_affected_rows($this->stmt);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->log($e);
        }
    }

    /**
     * Last Insert Id by PDO
     *
     * @return integer
     */
    public function lastInsertId()
    {
        try {
            $stmt = $this->execDbQuery('SELECT lastval()');
            if ($stmt) {
                $row = pg_fetch_row($stmt);
                return $row[0];
            }
            return false;
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->log($e);
        }
    }

    /**
     * Execute parameterised query
     *
     * @param string $sql  Parameterised query
     * @param array  $params Parameterised query params
     * @return void
     */
    public function execDbQuery($sql, $params = [])
    {
        $this->useDatabase();

        try {
            pg_prepare($this->db, 'pg_query', $sql);
            pg_execute($this->db, 'pg_query', $params);
        } catch (\Exception $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            $this->log($e);
        }
    }

    /**
     * Fetch row from statement
     *
     * @return array
     */
    public function fetch()
    {
        try {
            if ($this->stmt) {
                return pg_fetch_assoc($this->stmt);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->log($e);
        }
    }

    /**
     * Fetch all rows from statement
     *
     * @return array
     */
    public function fetchAll()
    {
        try {
            if ($this->stmt) {
                return pg_fetch_all($this->stmt);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->log($e);
        }
    }

    /**
     * Close statement cursor
     *
     * @return void
     */
    public function closeCursor()
    {
        try {
            if ($this->stmt) {
                pg_free_result($this->stmt);
            }
            if ($this->db) {
                pg_flush($this->db);
            }
        } catch (\Exception $e) {
            $this->log($e);
        }
    }

    /**
     * Log error
     *
     * @param \Exception $e
     * @return void
     * @throws \Exception
     */
    private function log($e)
    {
        throw new \Exception(pg_last_error($this->db), HttpStatus::$InternalServerError);
    }
}
