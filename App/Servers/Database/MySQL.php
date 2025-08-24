<?php
/**
 * Handling Database via MySQL
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
 * MySQL Database
 * php version 8.3
 *
 * @category  Database_MySQL
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MySql extends AbstractDatabase
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
     * @var null|\PDO
     */
    private $_pdo = null;

    /**
     * Executed query statement
     *
     * @var null|\PDOStatement
     */
    private $_stmt = null;

    /**
     * Executed query statement
     *
     * @var \PDOStatement[]
     */
    private $_stmts = [];

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
        if ($this->_pdo !== null) {
            return;
        }

        try {
            $this->_pdo = new \PDO(
                dsn: "mysql:host={$this->_hostname};port={$this->_port}",
                username: $this->_username,
                password: $this->_password,
                options: [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    // \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
                ]
            );

            if ($this->database !== null) {
                $this->useDatabase();
            }
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
                $this->_pdo->exec(statement: "USE `{$this->database}`");
            }
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
                $this->rollback();
            }
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
            $this->_pdo->beginTransaction();
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
                $this->_pdo->commit();
            }
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
                $this->_pdo->rollback();
            }
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
                return (int)$this->_stmt->rowCount();
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
            if ($this->_pdo->lastInsertId() !== false) {
                return (int)$this->_pdo->lastInsertId();
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
            if ($pushPop && $this->_stmt) {
                array_push($this->_stmts, $this->_stmt);
            }
            $this->_stmt = $this->_pdo->prepare(
                query: $sql,
                options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            if ($this->_stmt) {
                $this->_stmt->execute(params: $params);
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollback();
            }
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
                return $this->_stmt->fetch(mode: \PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
                return $this->_stmt->fetchAll(mode: \PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
                $this->_stmt->closeCursor();
                if ($pushPop && count(value: $this->_stmts)) {
                    $this->_stmt = array_pop(array: $this->_stmts);
                }
            }
        } catch (\PDOException $e) {
            if ((int)$this->_pdo->errorCode()) {
                $this->_log(e: $e);
            }
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
            message: $e->getMessage(),
            code: HttpStatus::$InternalServerError
        );
    }
}
