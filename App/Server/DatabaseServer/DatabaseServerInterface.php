<?php

/**
 * Database
 * php version 8.3
 *
 * @category  Database
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\DatabaseServer;

/**
 * Database Interface
 * php version 8.3
 *
 * @category  Database_Interface
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
interface DatabaseServerInterface
{
	/**
	 * Database Server Object
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
	 * Fetch single row from statement
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
