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

use Microservices\App\SessionHandler\Containers\SessionContainerInterface;
use Microservices\App\SessionHandler\Containers\SessionContainerHelper;

/**
 * Custom Session Handler using Redis
 * php version 7
 *
 * @category  SessionHandlerContainer_Redis
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class RedisBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	public $sessionServerHost = null;
	public $sessionServerPort = null;
	public $sessionServerUser = null;
	public $sessionServerPassword = null;
	public $sessionServerDb = null;

	private $sessionObject = null;


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
		try {
			if (
				$this->sessionObject->exists($sessionId)
				&& ($data = $this->sessionObject->get($sessionId))
			) {
				$sessionData = $this->decryptData(
					cipherText: $data
				);
				return $sessionData;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
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
		try {
			if (
				$this->sessionObject->set(
					$sessionId,
					$this->encryptData(
						plainText: $sessionData
					),
					$this->sessionOptions['cookie_lifetime']
				)
			) {
				return Constant::$TRUE;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return Constant::$FALSE;
	}

	/**
	 * Update Session
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
		return $this->setSession(
			sessionId: $sessionId,
			sessionData: $sessionData
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
		try {
			if (
				$this->sessionObject->expire(
					$sessionId,
					$this->sessionOptions['cookie_lifetime']
				)
			) {
				return Constant::$TRUE;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return Constant::$FALSE;
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
		return Constant::$TRUE;
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
		try {
			if ($this->sessionObject->del($sessionId)) {
				return Constant::$TRUE;
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
		return Constant::$FALSE;
	}

	/**
	 * Close Container
	 * 
	 * @return void
	 */
	public function closeSession(): void
	{
		$this->sessionObject = Constant::$NULL;
	}

	/**
	 * Connect
	 * 
	 * @return void
	 */
	private function connect(): void
	{
		try {
			if (
				!extension_loaded(
					extension: 'redis'
				)
			) {
				throw new \Exception(
					message: "Unable to find Redis extension",
					code: 500
				);
			}

			$connParamArray = [
				'host' => $this->sessionServerHost,
				'port' => (int)$this->sessionServerPort,
				'connectTimeout' => 2.5
			];

			if (
				$this->sessionServerUser !== null
				&& $this->sessionServerPassword !== null
			) {
				$connParamArray['auth'] = [
					$this->sessionServerUser,
					$this->sessionServerPassword
				];
			}

			$this->sessionObject = new \Redis( // phpcs:ignore
				$connParamArray
			);
			$this->sessionObject->select(
				$this->sessionServerDb
			);
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
		}
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
