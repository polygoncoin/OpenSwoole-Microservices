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
 * Custom Session Handler using MySql
 * php version 7
 *
 * @category  CustomSessionHandler_MySQL
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MySqlBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	public $mySqlServerHostname = null;
	public $mySqlServerPort = null;
	public $mySqlServerUsername = null;
	public $mySqlServerPassword = null;
	public $mySqlServerDB = null;
	public $mySqlServerTable = null;

	private $mySqlServerObj = null;

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
			SELECT `sessionData`
			FROM `{$this->mySqlServerDB}`.`{$this->mySqlServerTable}`
			WHERE `sessionId` = :sessionId AND lastAccessed > :lastAccessed
		";
		$params = [
			':sessionId' => $sessionId,
			':lastAccessed' => (Env::$timestamp - $this->sessionMaxLifetime)
		];
		if (
			($row = $this->getSql(sql: $sql, params: $params))
			&& isset($row['sessionData'])
		) {
			return $this->decryptData(cipherText: $row['sessionData']);
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
			INSERT INTO `{$this->mySqlServerDB}`.`{$this->mySqlServerTable}`
			SET
				`sessionData` = :sessionData,
				`lastAccessed` = :lastAccessed,
				`sessionId` = :sessionId
		";
		$params = [
			':sessionId' => $sessionId,
			':sessionData' => $this->encryptData(plainText: $sessionData),
			':lastAccessed' => Env::$timestamp
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
			UPDATE `{$this->mySqlServerDB}`.`{$this->mySqlServerTable}`
			SET
				`sessionData` = :sessionData,
				`lastAccessed` = :lastAccessed
			WHERE
				`sessionId` = :sessionId
		";
		$params = [
			':sessionId' => $sessionId,
			':sessionData' => $this->encryptData(plainText: $sessionData),
			':lastAccessed' => Env::$timestamp
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
			UPDATE `{$this->mySqlServerDB}`.`{$this->mySqlServerTable}`
			SET `lastAccessed` = :lastAccessed
			WHERE `sessionId` = :sessionId
		";
		$params = [
			':sessionId' => $sessionId,
			':lastAccessed' => Env::$timestamp
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
		$lastAccessed = Env::$timestamp - $sessionMaxLifetime;
		$sql = "
			DELETE FROM `{$this->mySqlServerDB}`.`{$this->mySqlServerTable}`
			WHERE `lastAccessed` < :lastAccessed
		";
		$params = [
			':lastAccessed' => $lastAccessed
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
			DELETE FROM `{$this->mySqlServerDB}`.`{$this->mySqlServerTable}`
			WHERE `sessionId` = :sessionId
		";
		$params = [
			':sessionId' => $sessionId
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
		$this->mySqlServerObj = null;
	}

	/**
	 * Connect
	 *
	 * @return void
	 */
	private function connect(): void
	{
		try {
			$this->mySqlServerObj = new \PDO(
				dsn: "mysql:host={$this->mySqlServerHostname}",
				username: $this->mySqlServerUsername,
				password: $this->mySqlServerPassword,
				options: [
					\PDO::ATTR_EMULATE_PREPARES => false,
				]
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
	private function getSql($sql, $params = []): mixed
	{
		$row = [];
		try {
			$stmt = $this->mySqlServerObj->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(params: $params);
			switch ($stmt->rowCount()) {
				case 0:
					$row = [];
					break;
				case 1:
					$row = $stmt->fetch();
					break;
				default:
					$row = false;
					break;
			}
			$stmt->closeCursor();
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return $row;
	}

	/**
	 * Execute SQL
	 *
	 * @param string $sql    SQL
	 * @param array  $params Params
	 *
	 * @return bool
	 */
	private function execSql($sql, $params = []): bool
	{
		try {
			$stmt = $this->mySqlServerObj->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(params: $params);
			$stmt->closeCursor();
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return true;
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
