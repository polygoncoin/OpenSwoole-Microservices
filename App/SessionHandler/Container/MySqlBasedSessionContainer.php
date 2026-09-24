<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\SessionHandler\Container;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\SessionHandler\Container\SessionContainerInterface;
use Microservices\App\SessionHandler\Container\SessionContainerHelper;

/**
 * Custom Session Handler using MySql
 * php version 7
 *
 * @category  CustomSessionHandler_MySQL
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MySqlBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	public $sessionServerHost = null;
	public $sessionServerPort = null;
	public $sessionServerUser = null;
	public $sessionServerPassword = null;
	public $sessionServerDb = null;
	public $sessionServerTable = null;

	private $mySqlServerObject = null;

	/**
	 * Initialize
	 *
	 * @param string $sessionSavePath Session Save Path
	 * @param string $sessionName     Session Name
	 *
	 * @return void
	 */
	public function init(
		$sessionSavePath,
		$sessionName
	): void {
		$this->connect();
	}

	/**
	 * For Custom Session Handler - Validate session id
	 *
	 * @param string $sessionId Session id
	 *
	 * @return bool|string
	 */
	public function getSession(
		$sessionId
	): bool|string {
		$sql = "
			SELECT `sessionData`
			FROM `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			WHERE `sessionId` = :sessionId AND lastAccessed > :lastAccessed
		";
		$paramArray = [
			':sessionId' => $sessionId,
			':lastAccessed' => (Env::$timestamp - $this->sessionMaxLifetime)
		];
		if (
			(
				$record = $this->getSql(
					sql: $sql,
					paramArray: $paramArray
				)
			)
			&& isset($record['sessionData'])
		) {
			return $this->decryptData(
				cipherText: $record['sessionData']
			);
		}
		return Constant::$FALSE;
	}

	/**
	 * For Custom Session Handler - Write session data
	 *
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 *
	 * @return bool|int
	 */
	public function setSession(
		$sessionId,
		$sessionData
	): bool|int {
		$sql = "
			INSERT INTO `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			SET
				`sessionData` = :sessionData,
				`lastAccessed` = :lastAccessed,
				`sessionId` = :sessionId
		";
		$paramArray = [
			':sessionId' => $sessionId,
			':sessionData' => $this->encryptData(
				plainText: $sessionData
			),
			':lastAccessed' => Env::$timestamp
		];

		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * For Custom Session Handler - Update session data
	 *
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 *
	 * @return bool|int
	 */
	public function updateSession(
		$sessionId,
		$sessionData
	): bool|int {
		$sql = "
			UPDATE `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			SET
				`sessionData` = :sessionData,
				`lastAccessed` = :lastAccessed
			WHERE
				`sessionId` = :sessionId
		";
		$paramArray = [
			':sessionId' => $sessionId,
			':sessionData' => $this->encryptData(
				plainText: $sessionData
			),
			':lastAccessed' => Env::$timestamp
		];

		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * For Custom Session Handler - Update session timestamp
	 *
	 * @param string $sessionId   Session id
	 * @param string $sessionData Session Data
	 *
	 * @return bool
	 */
	public function touchSession(
		$sessionId,
		$sessionData
	): bool {
		$sql = "
			UPDATE `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			SET `lastAccessed` = :lastAccessed
			WHERE `sessionId` = :sessionId
		";
		$paramArray = [
			':sessionId' => $sessionId,
			':lastAccessed' => Env::$timestamp
		];
		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * For Custom Session Handler - Cleanup old sessions
	 *
	 * @param integer $sessionMaxLifetime Session Max Lifetime
	 *
	 * @return bool
	 */
	public function gcSession(
		$sessionMaxLifetime
	): bool {
		$lastAccessed = Env::$timestamp - $sessionMaxLifetime;
		$sql = "
			DELETE FROM `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			WHERE `lastAccessed` < :lastAccessed
		";
		$paramArray = [
			':lastAccessed' => $lastAccessed
		];
		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param string $sessionId Session id
	 *
	 * @return bool
	 */
	public function deleteSession(
		$sessionId
	): bool {
		$sql = "
			DELETE FROM `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			WHERE `sessionId` = :sessionId
		";
		$paramArray = [
			':sessionId' => $sessionId
		];
		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * Close File Container
	 *
	 * @return void
	 */
	public function closeSession(): void
	{
		$this->mySqlServerObject = Constant::$NULL;
	}

	/**
	 * Connect
	 *
	 * @return void
	 */
	private function connect(): void
	{
		try {
			$this->mySqlServerObject = new \PDO(
				dsn: "mysql:host={$this->sessionServerHost}",
				username: $this->sessionServerUser,
				password: $this->sessionServerPassword,
				options: [
					\PDO::ATTR_EMULATE_PREPARES => Constant::$FALSE,
				]
			);
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
	}

	/**
	 * Get Session
	 *
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 *
	 * @return mixed
	 */
	private function getSql(
		$sql,
		$paramArray = []
	): mixed {
		$record = [];
		try {
			$stmt = $this->mySqlServerObject->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(
				paramArray: $paramArray
			);
			switch ($stmt->rowCount()) {
				case 0:
					$record = [];
					break;
				case 1:
					$record = $stmt->fetch();
					break;
				default:
					$record = Constant::$FALSE;
					break;
			}
			$stmt->closeCursor();
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return $record;
	}

	/**
	 * Execute Sql
	 *
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 *
	 * @return bool
	 */
	private function execSql(
		$sql,
		$paramArray = []
	): bool {
		try {
			$stmt = $this->mySqlServerObject->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(
				paramArray: $paramArray
			);
			$stmt->closeCursor();
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return Constant::$TRUE;
	}

	/**
	 * Manage Exception
	 *
	 * @param \Exception $e Exception
	 *
	 * @return never
	 */
	private function manageException(
		\Exception $e
	): never {
		throw new \Exception(
			message: $e->getMessage(),
			code: HttpStatus::$InternalServerError
		);
	}
}
