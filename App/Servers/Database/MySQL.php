<?php
namespace Microservices\App\Servers\Database;

use Microservices\App\HttpStatus;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Loading database server
 *
 * This class is built to handle MySql database operation
 *
 * @category   Database - MySql
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MySql extends AbstractDatabase
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
     * @var null|\PDO
     */
    private $pdo = null;

    /**
     * Executed query statement
     *
     * @var null|\PDOStatement
     */
    private $stmt = null;

    /**
     * Executed query statement
     *
     * @var \PDOStatement[]
     */
    private $stmts = [];

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
        if (!is_null($this->pdo)) return;

        try {
           $this->pdo = new \PDO(
                "mysql:host={$this->hostname};port={$this->port}",
                $this->username,
                $this->password,
                [
                    \PDO::ATTR_EMULATE_PREPARES => false,
//                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
                ]
            );

            if (!is_null($this->database)) {
                $this->useDatabase();
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
                $this->pdo->exec("USE `{$this->database}`");
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
                $this->rollback();
            }
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
           $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
                $this->pdo->commit();
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
                $this->pdo->rollback();
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
                return $this->stmt->rowCount();
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
        }
    }

    /**
     * Execute parameterised query
     *
     * @param string $sql  Parameterised query
     * @param array  $params Parameterised query params
     * @return void
     */
    public function execDbQuery($sql, $params = [], $pushPop = false)
    {
        $this->useDatabase();

        try {
            if ($pushPop && $this->stmt) {
                array_push($this->stmts, $this->stmt);
            }
            $this->stmt = $this->pdo->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            if ($this->stmt) {
                $this->stmt->execute($params);
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
                return $this->stmt->fetch(\PDO::FETCH_ASSOC);
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
                return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
        }
    }

    /**
     * Close statement cursor
     *
     * @return void
     */
    public function closeCursor($pushPop = false)
    {
        try {
            if ($this->stmt) {
                $this->stmt->closeCursor();
                if ($pushPop && count($this->stmts)) {
                    $this->stmt = array_pop($this->stmts);
                }
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log($e);
            }
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
        throw new \Exception($e->getMessage(), HttpStatus::$InternalServerError);
    }
}
