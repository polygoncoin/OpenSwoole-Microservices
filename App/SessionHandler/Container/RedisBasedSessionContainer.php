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

use Microservices\App\SessionHandler\Container\SessionContainerInterface;
use Microservices\App\SessionHandler\Container\SessionContainerHelper;

/**
 * Custom Session Handler using Redis
 * php version 7
 *
 * @category  CustomSessionHandler_Redis
 * @package   Openswoole_Microservices
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
	public $redisServerDB = null;

	private $redisServerObj = null;

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
		try {
			if (
				$this->redisServerObj->exists($sessionId)
				&& ($data = $this->redisServerObj->get($sessionId))
			) {
				return $this->decryptData(cipherText: $data);
			}
		} catch (\Exception $e) {
			$this->manageException(e: $e);
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
		try {
			if (
				$this->redisServerObj->set(
					$sessionId,
					$this->encryptData(plainText: $sessionData),
					$this->sessionMaxLifetime
				)
			) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return false;
	}

	/**
	 * Update Session
	 *
	 * @param string $sessionId   Session ID
	 * @param string $sessionData Session Data
	 *
	 * @return bool|int
	 */
	public function updateSession($sessionId, $sessionData): bool|int
	{
		return $this->setSession(
			sessionId: $sessionId,
			sessionData: $sessionData
		);
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
		try {
			if ($this->redisServerObj->expire($sessionId, $this->sessionMaxLifetime)) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return false;
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
		return true;
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
		try {
			if ($this->redisServerObj->del($sessionId)) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return false;
	}

	/**
	 * Close File Container
	 *
	 * @return void
	 */
	public function closeSession(): void
	{
		$this->redisServerObj = null;
	}

	/**
	 * Connect
	 *
	 * @return void
	 */
	private function connect(): void
	{
		try {
			if (!extension_loaded(extension: 'redis')) {
				throw new \Exception(
					message: "Unable to find Redis extension",
					code: 500
				);
			}

			$connParams = [
				'host' => $this->redisServerHostname,
				'port' => (int)$this->redisServerPort,
				'connectTimeout' => 2.5
			];

			if (
				$this->redisServerUsername !== null
				&& $this->redisServerPassword !== null
			) {
				$connParams['auth'] = [
					$this->redisServerUsername,
					$this->redisServerPassword
				];
			}

			$this->redisServerObj = new \Redis( // phpcs:ignore
				$connParams
			);
			$this->redisServerObj->select($this->redisServerDB);
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
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
