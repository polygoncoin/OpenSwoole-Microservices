<?php
namespace Microservices\App\Servers\Database;

use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Loading database server
 *
 * This class is built to handle MySQL database operation.
 *
 * @category   Database - MySQL
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MySQL extends AbstractDatabase
{
    /**
     * Database hostname
     *
     * @var string
     */
    private $hostname = null;

    /**
     * Database port
     *
     * @var string
     */
    private $port = null;

    /**
     * Database username
     *
     * @var string
     */
    private $username = null;

    /**
     * Database password
     *
     * @var string
     */
    private $password = null;

    /**
     * Database database
     *
     * @var string
     */
    public $database = null;

    /**
     * Database connection
     *
     * @var object
     */
    private $db = null;

    /**
     * Executed query statement
     *
     * @var object
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
           $this->db = new \PDO(
                "mysql:host=".$this->hostname,
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
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
                $this->db->exec("USE `{$this->database}`");
            }
        } catch (\PDOException $e) {
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
           $this->db->beginTransaction();
        } catch (\PDOException $e) {
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
               $this->db->commit();
            }
        } catch (\PDOException $e) {
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
               $this->db->rollback();
            }
        } catch (\PDOException $e) {
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
            return$this->db->lastInsertId();
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
    public function execDbQuery($sql, $params = [])
    {
        $this->useDatabase();

        try {
            $this->stmt = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $this->stmt->execute($params);
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
            }
        }
    }

    /**
     * Prepare Sql
     *
     * @param string $sql  SQL query
     * @return object
     */
    public function prepare($sql)
    {
        $this->useDatabase();

        try {
            $stmt = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
            }
        }
        return $stmt;
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
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
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
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
            }
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
                $this->stmt->closeCursor();
            }
        } catch (\PDOException $e) {
            if ((int)$this->db->errorCode()) {
                $this->logError($e);
            }
        }
    }

    /**
     * Log error
     *
     * @return void
     */
    private function logError($e)
    {
        throw new \Exception($e->getMessage(), 501);
    }
}
