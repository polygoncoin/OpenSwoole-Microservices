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
	public $mySqlServerDb = null;
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
	 * For Custom Session Handler - Validate session id
	 *
	 * @param string $sessionID Session id
	 *
	 * @return bool|string
	 */
	public function getSession($sessionID): bool|string
	{
		$sql = "
			SELECT `sessionData`
			FROM `{$this->mySqlServerDb}`.`{$this->mySqlServerTable}`
			WHERE `sessionID` = :sessionID AND lastAccessed > :lastAccessed
		";
		$paramArr = [
			':sessionID' => $sessionID,
			':lastAccessed' => (Env::$timestamp - $this->sessionMaxLifetime)
		];
		if (
			($row = $this->getSql(sql: $sql, paramArr: $paramArr))
			&& isset($row['sessionData'])
		) {
			return $this->decryptData(cipherText: $row['sessionData']);
		}
		return false;
	}

	/**
	 * For Custom Session Handler - Write session data
	 *
	 * @param string $sessionID   Session id
	 * @param string $sessionData Session Data
	 *
	 * @return bool|int
	 */
	public function setSession($sessionID, $sessionData): bool|int
	{
		$sql = "
			INSERT INTO `{$this->mySqlServerDb}`.`{$this->mySqlServerTable}`
			SET
				`sessionData` = :sessionData,
				`lastAccessed` = :lastAccessed,
				`sessionID` = :sessionID
		";
		$paramArr = [
			':sessionID' => $sessionID,
			':sessionData' => $this->encryptData(plainText: $sessionData),
			':lastAccessed' => Env::$timestamp
		];

		return $this->execSql(sql: $sql, paramArr: $paramArr);
	}

	/**
	 * For Custom Session Handler - Update session data
	 *
	 * @param string $sessionID   Session id
	 * @param string $sessionData Session Data
	 *
	 * @return bool|int
	 */
	public function updateSession($sessionID, $sessionData): bool|int
	{
		$sql = "
			UPDATE `{$this->mySqlServerDb}`.`{$this->mySqlServerTable}`
			SET
				`sessionData` = :sessionData,
				`lastAccessed` = :lastAccessed
			WHERE
				`sessionID` = :sessionID
		";
		$paramArr = [
			':sessionID' => $sessionID,
			':sessionData' => $this->encryptData(plainText: $sessionData),
			':lastAccessed' => Env::$timestamp
		];

		return $this->execSql(sql: $sql, paramArr: $paramArr);
	}

	/**
	 * For Custom Session Handler - Update session timestamp
	 *
	 * @param string $sessionID   Session id
	 * @param string $sessionData Session Data
	 *
	 * @return bool
	 */
	public function touchSession($sessionID, $sessionData): bool
	{
		$sql = "
			UPDATE `{$this->mySqlServerDb}`.`{$this->mySqlServerTable}`
			SET `lastAccessed` = :lastAccessed
			WHERE `sessionID` = :sessionID
		";
		$paramArr = [
			':sessionID' => $sessionID,
			':lastAccessed' => Env::$timestamp
		];
		return $this->execSql(sql: $sql, paramArr: $paramArr);
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
			DELETE FROM `{$this->mySqlServerDb}`.`{$this->mySqlServerTable}`
			WHERE `lastAccessed` < :lastAccessed
		";
		$paramArr = [
			':lastAccessed' => $lastAccessed
		];
		return $this->execSql(sql: $sql, paramArr: $paramArr);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param string $sessionID Session id
	 *
	 * @return bool
	 */
	public function deleteSession($sessionID): bool
	{
		$sql = "
			DELETE FROM `{$this->mySqlServerDb}`.`{$this->mySqlServerTable}`
			WHERE `sessionID` = :sessionID
		";
		$paramArr = [
			':sessionID' => $sessionID
		];
		return $this->execSql(sql: $sql, paramArr: $paramArr);
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
	 * @param string $sql      SQL query
	 * @param array  $paramArr SQL query params
	 *
	 * @return mixed
	 */
	private function getSql($sql, $paramArr = []): mixed
	{
		$row = [];
		try {
			$stmt = $this->mySqlServerObj->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(paramArr: $paramArr);
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
	 * @param string $sql      SQL query
	 * @param array  $paramArr SQL query params
	 *
	 * @return bool
	 */
	private function execSql($sql, $paramArr = []): bool
	{
		try {
			$stmt = $this->mySqlServerObj->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(paramArr: $paramArr);
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
