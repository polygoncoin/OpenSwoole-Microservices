<?php

/**
 * Sql Containers
 * php version 8.3
 *
 * @category  SqlContainers
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Servers\Containers\Sql;

/**
 * Sql Interface
 * php version 8.3
 *
 * @category  Sql_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface SqlInterface
{
    /**
     * Database connection
     *
     * @return void
     */
    public function connect(): void;

    /**
     * Use Database
     *
     * @return void
     */
    public function useDatabase(): void;

    /**
     * Begin transaction
     *
     * @return void
     */
    public function begin(): void;

    /**
     * Commit transaction
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback transaction
     *
     * @return void
     */
    public function rollBack(): void;

    /**
     * Affected Rows by PDO
     *
     * @return bool|int
     */
    public function affectedRows(): bool|int;

    /**
     * Last Insert Id by PDO
     *
     * @return bool|int
     */
    public function lastInsertId(): bool|int;

    /**
     * Execute Parameterized query
     *
     * @param string $sql     Parameterized query
     * @param array  $params  Parameterized query params
     * @param bool   $pushPop Push Pop result set stmt
     *
     * @return void
     */
    public function execDbQuery($sql, $params = [], $pushPop = false): void;

    /**
     * Fetch row from statement
     *
     * @return mixed
     */
    public function fetch(): mixed;

    /**
     * Fetch all rows from statement
     *
     * @return array|bool
     */
    public function fetchAll(): array|bool;

    /**
     * Close statement cursor
     *
     * @param bool $pushPop Push Pop result set stmt
     *
     * @return void
     */
    public function closeCursor($pushPop = false): void;
}
