<?php

/**
 * NoSql Database
 * php version 8.3
 *
 * @category  NoSql
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\NoSql;

use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Server\Container\NoSql\NoSqlInterface;

/**
 * MongoDb
 * php version 8.3
 *
 * @category  MongoDb
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MongoDb implements NoSqlInterface
{
	// "mongodb://<cacheServerUsername>:<cacheServerPassword>@<cluster-url>:<cacheServerPort>/<database-name>
	// ?retryWrites=true&w=majority"
	private $uri = null;

	/**
	 * Cache Server Hostname
	 *
	 * @var null|string
	 */
	private $cacheServerHostname = null;

	/**
	 * Cache Server Port
	 *
	 * @var null|int
	 */
	private $cacheServerPort = null;

	/**
	 * Cache Server Username
	 *
	 * @var null|string
	 */
	private $cacheServerUsername = null;

	/**
	 * Cache Server Password
	 *
	 * @var null|string
	 */
	private $cacheServerPassword = null;

	/**
	 * Cache Server DB
	 *
	 * @var null|string
	 */
	private $cacheServerDatabase = null;

	/**
	 * Cache collection
	 *
	 * @var null|string
	 */
	public $cacheServerTable = null;

	/**
	 * Cache Object
	 *
	 * @var null|\MongoDB\Customer
	 */
	private $cacheServerObj = null;

	/**
	 * Database Object
	 *
	 * @var null|Object
	 */
	private $cacheServerDatabaseObj = null;

	/**
	 * Collection Object
	 *
	 * @var null|Object
	 */
	private $collectionObj = null;

	/**
	 * Constructor
	 *
	 * @param string      $cacheServerHostname Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUsername Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDatabase Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 */
	public function __construct(
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDatabase,
		$cacheServerTable
	) {
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDatabase = $cacheServerDatabase;
		$this->cacheServerTable = $cacheServerTable;
	}

	/**
	 * Cache Server Object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void
	{
		if ($this->cacheServerObj !== null) {
			return;
		}

		try {
			if ($this->uri === null) {
				$UP = '';
				if (
					$this->cacheServerUsername !== null
					&& $this->cacheServerPassword !== null
				) {
					$UP = "{$this->cacheServerUsername}:{$this->cacheServerPassword}@";
				}
				$this->uri = 'mongodb://' . $UP
					. $this->cacheServerHostname . ':' . $this->cacheServerPort;
			}
			$this->cacheServerObj = new \MongoDB\Customer($this->uri);

			// Select a database
			$this->cacheServerDatabaseObj = $this->cacheServerObj->selectDatabase(
				$this->cacheServerDatabase
			);

			// Select a collection
			$this->collectionObj = $this->cacheServerDatabaseObj->selectCollection(
				$this->cacheServerTable
			);

			// Create the TTL index
			// Set the indexed field to 'expireOn' and expireAfterSeconds to 0
			$this->collectionObj->createIndex(
				['expireOn' => 1],
				['expireAfterSeconds' => 0]
			);
		} catch (\Exception $e) {
			throw new \Exception(
				message: $e->getMessage(),
				code: HttpStatus::$InternalServerError
			);
		}
	}

	/**
	 * Cache key exist
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function exist($key): mixed
	{
		$this->connect();

		$filter = ['key' => $key];

		if ($document = $this->collectionObj->findOne($filter)) {
			return true;
		}
		return false;
	}

	/**
	 * Get cache key
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function get($key): mixed
	{
		$this->connect();

		$filter = ['key' => $key];
		return $this->collectionObj->findOne($filter);
	}

	/**
	 * Set cache key
	 *
	 * @param string $key    Key
	 * @param string $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function set($key, $value, $expire = null): mixed
	{
		$this->connect();

		$document = [
			'key' => $key,
			'value' => $value
		];

		if ($expire === null) {
			if ($this->collectionObj->insertOne($document)) {
				return true;
			}
		} else {
			// Current UTC timestamp
			$document['expireOn'] = new MongoDB\BSON\UTCDateTime(
				(Env::$timestamp + $expire) * 1000
			);
			if ($this->collectionObj->insertOne($document)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Increment cache key with offset
	 *
	 * @param string $key    Key
	 * @param int    $offset Offset
	 *
	 * @return int
	 */
	public function increment($key, $offset = 1): int
	{
		$this->connect();

		$filter = ['key' => $key];
		$update = ['$inc' => ['value' => $offset]];
		$result = $this->collectionObj->updateOne($filter, $update);

		return $result->getModifiedCount();
	}

	/**
	 * Delete cache key
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function delete($key): mixed
	{
		$this->connect();

		$filter = ['key' => $key];
		if ($this->collectionObj->deleteOne($filter)) {
			return true;
		}
		return false;
	}
}
