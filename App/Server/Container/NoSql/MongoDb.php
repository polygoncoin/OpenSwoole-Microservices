<?php

/**
 * NoSql Database
 * php version 8.3
 *
 * @category  NoSql
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\NoSql;

use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Server\Container\NoSql\NoSqlInterface;

/**
 * MongoDb
 * php version 8.3
 *
 * @category  MongoDb
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MongoDb implements NoSqlInterface
{
	// "mongodb://<cacheServerUser>:<cacheServerPassword>@<cluster-url>:<cacheServerPort>/<database-name>
	// ?retryWrites=true&w=majority"
	private $uri = null;

	/**
	 * Cache Server Hostname
	 * 
	 * @var null|string
	 */
	private $cacheServerHost = null;

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
	private $cacheServerUser = null;

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
	private $cache = null;

	/**
	 * Database Object
	 * 
	 * @var null|Object
	 */
	private $cacheServerDbObject = null;

	/**
	 * Collection Object
	 * 
	 * @var null|Object
	 */
	private $collectionObject = null;

	/**
	 * Constructor
	 * 
	 * @param string      $cacheServerHost Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUser Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDb Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 */
	public function __construct(
		$cacheServerHost,
		$cacheServerPort,
		$cacheServerUser,
		$cacheServerPassword,
		$cacheServerDb,
		$cacheServerTable
	) {
		$this->cacheServerHost = $cacheServerHost;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUser = $cacheServerUser;
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
		if ($this->cache !== Constant::$NULL) {
			return;
		}

		try {
			if ($this->uri === Constant::$NULL) {
				$UP = '';
				if (
					$this->cacheServerUser !== Constant::$NULL
					&& $this->cacheServerPassword !== Constant::$NULL
				) {
					$UP = "{$this->cacheServerUser}:{$this->cacheServerPassword}@";
				}
				$this->uri = 'mongodb://' . $UP
					. $this->cacheServerHost . ':' . $this->cacheServerPort;
			}
			$this->cache = new \MongoDB\Customer(
				$this->uri
			);

			// Select a database
			$this->cacheServerDbObject = $this->cache->selectDatabase(
				$this->cacheServerDb
			);

			// Select a collection
			$this->collectionObject = $this->cacheServerDbObject->selectCollection(
				$this->cacheServerTable
			);

			// Create the TTL index
			// Set the indexed field to 'expireOn' and expireAfterSeconds to 0
			$this->collectionObject->createIndex(
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
	public function exist(
		$key
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		$filter = ['key' => $key];

		if (
			$document = $this->collectionObject->findOne(
				$filter
			)
		) {
			return Constant::$TRUE;
		}
		return Constant::$FALSE;
	}

	/**
	 * Get cache key
	 * 
	 * @param string $key Key
	 * 
	 * @return mixed
	 */
	public function get(
		$key
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		$filter = ['key' => $key];

		$return = CommonFunction::jsonDecode(
			value: $this->collectionObject->findOne(
				$filter
			)
		);

		return $return;
	}

	/**
	 * Set cache key
	 * 
	 * @param string $key    Key
	 * @param mixed  $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 * 
	 * @return mixed
	 */
	public function set(
		$key,
		$value,
		$expire = null
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		$value = json_encode(
			value: $value
		);

		$document = [
			'key' => $key,
			'value' => $value
		];

		if ($expire === Constant::$NULL) {
			if ($this->collectionObject->insertOne($document)) {
				return Constant::$TRUE;
			}
		} else {
			// Current UTC timestamp
			$document['expireOn'] = new MongoDB\BSON\UTCDateTime(
				(Env::$timestamp + $expire) * 1000
			);
			if ($this->collectionObject->insertOne($document)) {
				return Constant::$TRUE;
			}
		}
		return Constant::$FALSE;
	}

	/**
	 * Increment cache key with offset
	 * 
	 * @param string $key    Key
	 * @param int    $offset Offset
	 * 
	 * @return mixed
	 */
	public function increment(
		$key,
		$offset = 1
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		$filter = ['key' => $key];
		$update = ['$inc' => ['value' => $offset]];
		$result = $this->collectionObject->updateOne(
			$filter,
			$update
		);

		return $result->getModifiedCount();
	}

	/**
	 * Delete cache key
	 * 
	 * @param string $key Key
	 * 
	 * @return mixed
	 */
	public function delete(
		$key
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		$filter = ['key' => $key];
		if ($this->collectionObject->deleteOne($filter)) {
			return Constant::$TRUE;
		}
		return Constant::$FALSE;
	}
}
