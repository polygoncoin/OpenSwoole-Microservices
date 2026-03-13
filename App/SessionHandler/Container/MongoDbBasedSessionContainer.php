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
 * Custom Session Handler using Redis
 * php version 7
 *
 * @category  CustomSessionHandler_MongoDb
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MongoDbBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	// "mongodb://<username>:<password>@<cluster-url>:<port>/<database-name>
	// ?retryWrites=true&w=majority"
	public $mongoDbServerUri = null;

	public $mongoDbServerHostname = null;
	public $mongoDbServerPort = null;
	public $mongoDbServerUsername = null;
	public $mongoDbServerPassword = null;
	public $mongoDbServerDB = null;
	public $mongoDbServerCollection = null;

	private $mongoDbServerObj = null;
	private $dbObj = null;
	private $collectionObj = null;

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
			$filter = ['sessionId' => $sessionId];

			if ($document = $this->collectionObj->findOne($filter)) {
				$lastAccessed = Env::$timestamp - $this->sessionMaxLifetime;
				if ($document['lastAccessed'] > $lastAccessed) {
					return $this->decryptData(cipherText: $document['sessionData']);
				}
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
			$document = [
				"sessionId" => $sessionId,
				"lastAccessed" => Env::$timestamp,
				"sessionData" => $this->encryptData(plainText: $sessionData)
			];
			if ($this->collectionObj->insertOne($document)) {
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
		try {
			$filter = ['sessionId' => $sessionId];
			$update = [
				'$set' => [
					'lastAccessed' => Env::$timestamp,
					"sessionData" => $this->encryptData(plainText: $sessionData)
				]
			];
			if ($this->collectionObj->updateOne($filter, $update)) {
				return true;
			}
		} catch (\Exception $e) {
			$this->manageException(e: $e);
		}
		return false;
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
			$filter = ['sessionId' => $sessionId];
			$update = [
				'$set' => [
					'lastAccessed' => Env::$timestamp
				]
			];

			if ($this->collectionObj->updateOne($filter, $update)) {
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
			$filter = ['sessionId' => $sessionId];

			if ($this->collectionObj->deleteOne($filter)) {
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
		$this->mongoDbServerObj = null;
	}

	/**
	 * Connect
	 *
	 * @return void
	 */
	private function connect(): void
	{
		try {
			if ($this->mongoDbServerUri === null) {
				$UP = '';
				if ($this->mongoDbServerUsername !== null && $this->mongoDbServerPassword !== null) {
					$UP = "{$this->mongoDbServerUsername}:{$this->mongoDbServerPassword}@";
				}
				$this->mongoDbServerUri = 'mongodb://' . $UP
					. $this->mongoDbServerHostname . ':' . $this->mongoDbServerPort;
			}
			$this->mongoDbServerObj = new \MongoDB\Customer($this->mongoDbServerUri);

			// Select a database
			$this->dbObj = $this->mongoDbServerObj->selectDatabase($this->mongoDbServerDB);

			// Select a collection
			$this->collectionObj = $this->dbObj->selectCollection($this->mongoDbServerCollection);
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
