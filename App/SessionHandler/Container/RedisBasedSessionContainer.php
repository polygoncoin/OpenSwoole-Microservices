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
use Microservices\App\HttpStatus;
use Microservices\App\SessionHandler\Container\SessionContainerInterface;
use Microservices\App\SessionHandler\Container\SessionContainerHelper;

/**
 * Custom Session Handler using Redis
 * php version 7
 * 
 * @category  CustomSessionHandler_Redis
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RedisBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	public $redisServerHostname = null;
	public $redisServerPort = null;
	public $redisServerUsername = null;
	public $redisServerPassword = null;
	public $redisServerDatabase = null;

	private $redisServerObject = null;

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
				$this->redisServerObject->exists($sessionId)
				&& ($data = $this->redisServerObject->get($sessionId))
			) {
				return $this->decryptData(
					cipherText: $data
				);
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
				$this->redisServerObject->set(
					$sessionId,
					$this->encryptData(
						plainText: $sessionData
					),
					$this->sessionMaxLifetime
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
				$this->redisServerObject->expire(
					$sessionId,
					$this->sessionMaxLifetime
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
			if ($this->redisServerObject->del($sessionId)) {
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
	 * Close File Container
	 * 
	 * @return void
	 */
	public function closeSession(): void
	{
		$this->redisServerObject = Constant::$NULL;
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
					code: HttpStatus::$InternalServerError
				);
			}

			$connParamArray = [
				'host' => $this->redisServerHostname,
				'port' => (int)$this->redisServerPort,
				'connectTimeout' => 2.5
			];

			if (
				$this->redisServerUsername !== Constant::$NULL
				&& $this->redisServerPassword !== Constant::$NULL
			) {
				$connParamArray['auth'] = [
					$this->redisServerUsername,
					$this->redisServerPassword
				];
			}

			$this->redisServerObject = new \Redis( // phpcs:ignore
				$connParamArray
			);
			$this->redisServerObject->select(
				$this->redisServerDatabase
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
			code: HttpStatus::$InternalServerError
		);
	}
}
