<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandlerContainer
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\SessionHandler\Containers;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\SessionHandler\Containers\SessionContainerInterface;
use Microservices\App\SessionHandler\Containers\SessionContainerHelper;

/**
 * Custom Session Handler using MySQL
 * php version 7
 *
 * @category  SessionHandlerContainer_MySQL
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
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

	private $webSession = null;


	/**
	 * Constructor
	 */
	public function __construct() {

	}

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
		$customerSessionId = $this->getCustomerSessionId(
			sessionId: $sessionId
		);
		$sql = "
			SELECT *
			FROM `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			WHERE `session_id` = :session_id AND `last_accessed` > :last_accessed
		";
		$paramArray = [
			':session_id' => $customerSessionId,
			':last_accessed' => (Env::$timestamp - $this->sessionOptions['cookie_lifetime'])
		];
		if (
			(
				$record = $this->getSql(
					sql: $sql,
					paramArray: $paramArray
				)
			)
			&& isset($record['session_data'])
		) {
			$sessionData = $this->decryptData(
				cipherText: $record['session_data']
			);
			return $sessionData;
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
		$customerSessionId = $this->getCustomerSessionId(
			sessionId: $sessionId
		);
		$sql = "
			INSERT INTO `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			SET
				`session_id` = :session_id,
				`session_data` = :session_data,
				`last_accessed` = :last_accessed
		";
		$paramArray = [
			':session_id' => $customerSessionId,
			':session_data' => $this->encryptData(
				plainText: $sessionData
			),
			':last_accessed' => Env::$timestamp
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
		$customerSessionId = $this->getCustomerSessionId(
			sessionId: $sessionId
		);
		$sql = "
			UPDATE `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			SET
				`session_data` = :session_data,
				`last_accessed` = :last_accessed
			WHERE
				`session_id` = :session_id
		";
		$paramArray = [
			':session_id' => $customerSessionId,
			':session_data' => $this->encryptData(
				plainText: $sessionData
			),
			':last_accessed' => Env::$timestamp
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
		$customerSessionId = $this->getCustomerSessionId(
			sessionId: $sessionId
		);
		$sql = "
			UPDATE `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			SET `last_accessed` = :last_accessed
			WHERE `session_id` = :session_id
		";
		$paramArray = [
			':session_id' => $customerSessionId,
			':last_accessed' => Env::$timestamp
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
			WHERE `last_accessed` < :last_accessed
		";
		$paramArray = [
			':last_accessed' => $lastAccessed
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
		$customerSessionId = $this->getCustomerSessionId(
			sessionId: $sessionId
		);
		$sql = "
			DELETE FROM `{$this->sessionServerDb}`.`{$this->sessionServerTable}`
			WHERE `session_id` = :session_id
		";
		$paramArray = [
			':session_id' => $customerSessionId
		];
		return $this->execSql(
			sql: $sql,
			paramArray: $paramArray
		);
	}

	/**
	 * Close Container
	 * 
	 * @return void
	 */
	public function closeSession(): void
	{
		$this->webSession = Constant::$NULL;
	}

	/**
	 * Connect
	 * 
	 * @return void
	 */
	private function connect(): void
	{
		try {
			$this->webSession = new \PDO(
				dsn: "mysql:host={$this->sessionServerHost}",
				username: $this->sessionServerUser,
				password: $this->sessionServerPassword,
				options: [
					\PDO::ATTR_EMULATE_PREPARES => false,
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
			$stmt = $this->webSession->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(
				$paramArray
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
			$stmt = $this->webSession->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			$stmt->execute(
				$paramArray
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
			code: 500
		);
	}
}
