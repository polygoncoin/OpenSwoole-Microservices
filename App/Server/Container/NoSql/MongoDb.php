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
	private $cacheServerDb = null;

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
	private $cacheServerDbObj = null;

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
	 * @param null|string $cacheServerDb       Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 */
	public function __construct(
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDb,
		$cacheServerTable
	) {
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDb = $cacheServerDb;
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
			$this->cacheServerDbObj = $this->cacheServerObj->selectDatabase($this->cacheServerDb);

			// Select a collection
			$this->collectionObj = $this->cacheServerDbObj->selectCollection($this->cacheServerTable);

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
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	public function cacheExist($cacheKey): mixed
	{
		$this->connect();

		$filter = ['key' => $cacheKey];

		if ($document = $this->collectionObj->findOne($filter)) {
			return true;
		}
		return false;
	}

	/**
	 * Get cache key
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	public function cacheGet($cacheKey): mixed
	{
		$this->connect();

		$filter = ['key' => $cacheKey];
		return $this->collectionObj->findOne($filter);
	}

	/**
	 * Set cache key
	 *
	 * @param string $cacheKey Cache key
	 * @param string $value    Cache value
	 * @param int    $expire   Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function cacheSet($cacheKey, $value, $expire = null): mixed
	{
		$this->connect();

		$document = [
			'key' => $cacheKey,
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
	 * @param string $cacheKey Cache key
	 * @param int    $offset   Offset
	 *
	 * @return int
	 */
	public function cacheIncrement($cacheKey, $offset = 1): int
	{
		$this->connect();

		$filter = ['key' => $cacheKey];
		$update = ['$inc' => ['value' => $offset]];
		$result = $this->collectionObj->updateOne($filter, $update);

		return $result->getModifiedCount();
	}

	/**
	 * Delete cache key
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	public function cacheDelete($cacheKey): mixed
	{
		$this->connect();

		$filter = ['key' => $cacheKey];
		if ($this->collectionObj->deleteOne($filter)) {
			return true;
		}
		return false;
	}
}
