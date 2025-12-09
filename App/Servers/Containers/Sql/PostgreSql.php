<?php

/**
 * Sql Database
 * php version 8.3
 *
 * @category  Sql
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Servers\Containers\Sql;

use Microservices\App\HttpStatus;
use Microservices\App\Servers\Containers\Sql\SqlInterface;

/**
 * PostgreSql Database
 * php version 8.3
 *
 * @category  PostgreSql
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PostgreSql implements SqlInterface
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
     * @var bool
     */
    public $beganTransaction = false;

    /**
     * Constructor
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
        $database
    ) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * Database connection
     *
     * @return void
     */
    public function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        try {
            $pdo = new PDO($dsn, $user, $password);
            $this->pdo = new \PDO(
                dsn: "pgsql:host={$this->hostname};port={$this->port};dbname={$this->database}",
                username: $this->username,
                password: $this->password,
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    public function begin(): void
    {
        $this->connect();

        $this->beganTransaction = true;
        try {
            $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
                $this->pdo->commit();
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
            }
        }
    }

    /**
     * Rollback transaction
     *
     * @return void
     */
    public function rollBack(): void
    {
        try {
            if ($this->beganTransaction) {
                $this->beganTransaction = false;
                $this->pdo->rollBack();
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
            if ($this->stmt) {
                return $this->stmt->rowCount();
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollBack();
            }
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
            if ($this->stmt !== false) {
                return $this->stmt->fetchColumn();
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollBack();
            }
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
        $this->connect();

        try {
            if ($pushPop && $this->stmt) {
                array_push($this->stmts, $this->stmt);
            }
            $this->stmt = $this->pdo->prepare(
                query: $sql,
                options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            if ($this->stmt) {
                $this->stmt->execute(params: $params);
            }
        } catch (\PDOException $e) {
            if ($this->beganTransaction) {
                $this->rollBack();
            }
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
            if ($this->stmt) {
                return $this->stmt->fetch(mode: \PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
            if ($this->stmt) {
                return $this->stmt->fetchAll(mode: \PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
            if ($this->stmt) {
                $this->stmt->closeCursor();
                if ($pushPop && count(value: $this->stmts)) {
                    $this->stmt = array_pop(array: $this->stmts);
                }
            }
        } catch (\PDOException $e) {
            if ((int)$this->pdo->errorCode()) {
                $this->log(e: $e);
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
    private function log($e): never
    {
        throw new \Exception(
            message: $e->getMessage(),
            code: HttpStatus::$InternalServerError
        );
    }
}
