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
 * Custom Session Handler using Redis
 * php version 7
 *
 * @category  SessionHandlerContainer_MongoDb
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class MongoDbBasedSessionContainer extends SessionContainerHelper implements
	SessionContainerInterface
{
	// "mongodb://<username>:<password>@<cluster-url>:<port>/<database-name>
	// ?retryWrites=true&w=majority"
	public $mongoDbServerUri = null;

	public $sessionServerHost = null;
	public $sessionServerPort = null;
	public $sessionServerUser = null;
	public $sessionServerPassword = null;
	public $sessionServerDb = null;
	public $sessionServerTable = null;

	private $webSession = null;
	private $db = null;
	private $collectionObject = null;


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
			$filter = ['sessionId' => $sessionId];

			if ($document = $this->collectionObject->findOne($filter)) {
				$lastAccessed = Env::$timestamp - $this->sessionOptions['cookie_lifetime'];
				if ($document['lastAccessed'] > $lastAccessed) {
					$sessionData = $this->decryptData(
						cipherText: $document['sessionData']
					);
					return $sessionData;
				}
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
			$document = [
				"sessionId" => $sessionId,
				"lastAccessed" => Env::$timestamp,
				"sessionData" => $this->encryptData(
					plainText: $sessionData
				)
			];
			if ($this->collectionObject->insertOne($document)) {
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
		try {
			$filter = ['sessionId' => $sessionId];
			$update = [
				'$set' => [
					'lastAccessed' => Env::$timestamp,
					"sessionData" => $this->encryptData(
						plainText: $sessionData
					)
				]
			];
			if (
				$this->collectionObject->updateOne(
					$filter,
					$update
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
			$filter = ['sessionId' => $sessionId];
			$update = [
				'$set' => [
					'lastAccessed' => Env::$timestamp
				]
			];

			if (
				$this->collectionObject->updateOne(
					$filter,
					$update
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
			$filter = ['sessionId' => $sessionId];

			if ($this->collectionObject->deleteOne($filter)) {
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
			if ($this->mongoDbServerUri === Constant::$NULL) {
				$UP = '';
				if (
					$this->sessionServerUser !== null
					&& $this->sessionServerPassword !== null
				) {
					$UP = "{$this->sessionServerUser}:{$this->sessionServerPassword}@";
				}
				$this->mongoDbServerUri = 'mongodb://' . $UP
					. $this->sessionServerHost . ':' . $this->sessionServerPort;
			}
			$this->webSession = new \MongoDB\Customer(
				$this->mongoDbServerUri
			);

			// Select a database
			$this->db = $this->webSession->selectDatabase(
				$this->sessionServerDb
			);

			// Select a collection
			$this->collectionObject = $this->db->selectCollection(
				$this->sessionServerTable
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
