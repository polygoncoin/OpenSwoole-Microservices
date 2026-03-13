<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\SessionHandler\Container;

use Microservices\App\Env;
use Microservices\App\SessionHandler\Container\SessionContainerInterface;
use Microservices\App\SessionHandler\Container\SessionContainerHelper;

/**
 * Custom Session Handler using PostgreSql
 * php version 7
 *
 * @category  CustomSessionHandler_PgSql
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	public $pgSqlServerHostname = null;
	public $pgSqlServerPort = null;
	public $pgSqlServerUsername = null;
	public $pgSqlServerPassword = null;
	public $pgSqlServerDB = null;
	public $pgSqlServerTable = null;

	private $pgSqlServerObj = null;

	/**
	 * Initialize
	 *
	 * @param string $sessionSavePath Session Save Path
	 * @param string $sessionName     Session Name
	 *
	 * @return void
	 */
	public function init($sessionSavePath, $sessionName): void
	{
		$this->connect();
	}

	/**
	 * For Custom Session Handler - Validate session ID
	 *
	 * @param string $sessionId Session ID
	 *
	 * @return bool|string
	 */
	public function getSession($sessionId): bool|string
	{
		$sql = "
			SELECT session_data
			FROM {$this->pgSqlServerTable}
			WHERE session_id = $1 AND last_accessed > $2
		";
		$params = [
			$sessionId,
			(Env::$timestamp - $this->sessionMaxLifetime)
		];

		$row = $this->getSql(sql: $sql, params: $params);
		if (isset($row['session_data'])) {
			return $this->decryptData(cipherText: $row['session_data']);
		}
		return false;
	}

	/**
	 * For Custom Session Handler - Write session data
	 *
	 * @param string $sessionId   Session ID
	 * @param string $sessionData Session Data
	 *
	 * @return bool|int
	 */
	public function setSession($sessionId, $sessionData): bool|int
	{
		$sql = "
			INSERT INTO {$this->pgSqlServerTable} (session_id, last_accessed, session_data)
			VALUES ($1, $2, $3)
		";
		$params = [
			$sessionId,
			Env::$timestamp,
			$this->encryptData(plainText: $sessionData),
		];

		return $this->execSql(sql: $sql, params: $params);
	}

	/**
	 * For Custom Session Handler - Update session data
	 *
	 * @param string $sessionId   Session ID
	 * @param string $sessionData Session Data
	 *
	 * @return bool|int
	 */
	public function updateSession($sessionId, $sessionData): bool|int
	{
		$sql = "
			UPDATE {$this->pgSqlServerTable}
			SET
				last_accessed = $1,
				session_data = $2
			WHERE
				session_id = $3
		";
		$params = [
			Env::$timestamp,
			$this->encryptData(plainText: $sessionData),
			$sessionId
		];

		return $this->execSql(sql: $sql, params: $params);
	}

	/**
	 * For Custom Session Handler - Update session timestamp
	 *
	 * @param string $sessionId   Session ID
	 * @param string $sessionData Session Data
	 *
	 * @return bool
	 */
	public function touchSession($sessionId, $sessionData): bool
	{
		$sql = "
			UPDATE {$this->pgSqlServerTable}
			SET last_accessed = $1
			WHERE session_id = $2
		";
		$params = [
			Env::$timestamp,
			$sessionId
		];
		return $this->execSql(sql: $sql, params: $params);
	}

	/**
	 * For Custom Session Handler - Cleanup old sessions
	 *
	 * @param integer $sessionMaxLifetime Session Max Lifetime
	 *
	 * @return bool
	 */
	public function gcSession($sessionMaxLifetime): bool
	{
		$sql = "
			DELETE FROM {$this->pgSqlServerTable}
			WHERE last_accessed < $1
		";
		$params = [
			(Env::$timestamp - $sessionMaxLifetime)
		];
		return $this->execSql(sql: $sql, params: $params);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param string $sessionId Session ID
	 *
	 * @return bool
	 */
	public function deleteSession($sessionId): bool
	{
		$sql = "
			DELETE FROM {$this->pgSqlServerTable}
			WHERE session_id = $1
		";
		$params = [
			$sessionId
		];
		return $this->execSql(sql: $sql, params: $params);
	}

	/**
	 * Close File Container
	 *
	 * @return void
	 */
	public function closeSession(): void
	{
		pg_close($this->pgSqlServerObj);
		$this->pgSqlServerObj = null;
	}

	/**
	 * Connect
	 *
	 * @return void
	 */
	private function connect(): void
	{
		try {
			$UP = '';
			if (
				$this->pgSqlServerUsername !== null
				&& $this->pgSqlServerPassword !== null
			) {
				$UP = "user={$this->pgSqlServerUsername} password={$this->pgSqlServerPassword}";
			}
			$this->pgSqlServerObj = pg_connect(
				"host={$this->pgSqlServerHostname} "
				. "port={$this->pgSqlServerPort} "
				. "dbname={$this->pgSqlServerDB} {$UP}"
			);
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
	}

	/**
	 * Get Session
	 *
	 * @param string $sql    SQL
	 * @param array  $params Params
	 *
	 * @return mixed
	 */
	private function getSql($sql, $params): mixed
	{
		try {
			// Execute the query with parameters
			$result = pg_query_params($this->pgSqlServerObj, $sql, $params);
			if ($result) {
				$row = [];
				$rowsCount = pg_num_rows($result);
				if ($rowsCount === 1) {
					$row = pg_fetch_assoc($result);
				}
				pg_free_result($result);
				return $row;
			}
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return false;
	}

	/**
	 * Execute SQL
	 *
	 * @param string $sql    SQL
	 * @param array  $params Params
	 *
	 * @return bool
	 */
	private function execSql($sql, $params): bool
	{
		try {
			$result = pg_query_params($this->pgSqlServerObj, $sql, $params);
			if ($result) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return false;
	}

	/**
	 * Manage Exception
	 *
	 * @param \Exception $e Exception
	 *
	 * @return never
	 */
	private function manageException(\Exception $e): never
	{
		die($e->getMessage());
	}
}
