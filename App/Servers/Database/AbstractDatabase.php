<?php
/**
 * Database
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

/**
 * Database Abstract Class
 * php version 8.3
 *
 * @category  Database_Abstract_Class
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
abstract class AbstractDatabase
{
    /**
     * Database database
     *
     * @var null|string
     */
    public $database = null;

    /**
     * Transaction started flag
     *
     * @var bool
     */
    public $beganTransaction = false;

    /**
     * Database connection
     *
     * @return void
     */
    abstract public function connect(): void;

    /**
     * Use Database
     *
     * @return void
     */
    abstract public function useDatabase(): void;

    /**
     * Begin transaction
     *
     * @return void
     */
    abstract public function begin(): void;

    /**
     * Commit transaction
     *
     * @return void
     */
    abstract public function commit(): void;

    /**
     * Rollback transaction
     *
     * @return void
     */
    abstract public function rollback(): void;

    /**
     * Affected Rows by PDO
     *
     * @return bool|int
     */
    abstract public function affectedRows(): bool|int;

    /**
     * Last Insert Id by PDO
     *
     * @return bool|int
     */
    abstract public function lastInsertId(): bool|int;

    /**
     * Execute Parameterized query
     *
     * @param string $sql     Parameterized query
     * @param array  $params  Parameterized query params
     * @param bool   $pushPop Push Pop result set stmt
     *
     * @return void
     */
    abstract public function execDbQuery($sql, $params = [], $pushPop = false): void;

    /**
     * Fetch single row from statement
     *
     * @return mixed
     */
    abstract public function fetch(): mixed;

    /**
     * Fetch all rows from statement
     *
     * @return array|bool
     */
    abstract public function fetchAll(): array|bool;

    /**
     * Close statement cursor
     *
     * @param bool $pushPop Push Pop result set stmt
     *
     * @return void
     */
    abstract public function closeCursor($pushPop = false): void;
}
