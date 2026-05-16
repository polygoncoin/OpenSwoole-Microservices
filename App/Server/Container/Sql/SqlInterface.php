<?php

/**
 * SQL Container
 * php version 8.3
 *
 * @category  SqlContainers
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\Sql;

/**
 * SQL Interface
 * php version 8.3
 *
 * @category  Sql_Interface
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface SqlInterface
{
	/**
	 * Connect Database
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
	 * Affected row count
	 *
	 * @return bool|int
	 */
	public function affectedRowCount(): bool|int;

	/**
	 * Last insert id
	 *
	 * @return bool|int
	 */
	public function lastInsertId(): bool|int;

	/**
	 * Execute query
	 *
	 * @param string $sql      SQL query
	 * @param array  $paramArr SQL query params
	 * @param bool   $pushPop  Push Pop result set stmt
	 *
	 * @return void
	 */
	public function execDbQuery($sql, $paramArr = [], $pushPop = false): void;

	/**
	 * Fetch row
	 *
	 * @return mixed
	 */
	public function fetch(): mixed;

	/**
	 * Fetch all rows
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
