<?php

/**
 * Handling Cache via MongoDb
 * php version 8.3
 * 
 * @category  Cache
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\CacheServer;

use Microservices\App\Constant;
use Microservices\App\HttpStatus;
use Microservices\App\Server\CacheServer\CacheServerInterface;
use Microservices\App\Server\Container\NoSql\MongoDb as Cache_MongoDb;

/**
 * Caching via MongoDb
 * php version 8.3
 * 
 * @category  Cache_MongoDb
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MongoDbCache implements CacheServerInterface
{
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
	 * Cache Server Object
	 * 
	 * @var null|Cache_MongoDb
	 */
	private $noSqlServerObject = null;

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
	public function connectCache(): void
	{
		if ($this->noSqlServerObject !== Constant::$NULL) {
			return;
		}

		try {
			$this->noSqlServerObject = new Cache_MongoDb(
				cacheServerHostname: $this->cacheServerHostname,
				cacheServerPort: $this->cacheServerPort,
				cacheServerUsername: $this->cacheServerUsername,
				cacheServerPassword: $this->cacheServerPassword,
				cacheServerDatabase: $this->cacheServerDatabase,
				cacheServerTable: $this->cacheServerTable
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
	public function cacheExist(
		$cacheKey
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return Constant::$FALSE;
		}

		return $this->noSqlServerObject->exist(
			key: $cacheKey
		);
	}

	/**
	 * Get cache key
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	public function cacheGet(
		$cacheKey
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return Constant::$FALSE;
		}

		return $this->noSqlServerObject->get(
			key: $cacheKey
		);
	}

	/**
	 * Set cache key
	 * 
	 * @param string $cacheKey    Cache key
	 * @param mixed  $cacheValue  Cache value
	 * @param int    $cacheExpire Seconds to expire. Default 0 - doesn't expire
	 * 
	 * @return mixed
	 */
	public function cacheSet(
		$cacheKey,
		$cacheValue,
		$cacheExpire = null
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return Constant::$FALSE;
		}

		return $this->noSqlServerObject->set(
			key: $cacheKey,
			value: $cacheValue,
			expire: $cacheExpire
		);
	}

	/**
	 * Increment cache key with offset
	 * 
	 * @param string $cacheKey    Cache key
	 * @param int    $cacheOffset Offset
	 * 
	 * @return mixed
	 */
	public function cacheIncrement(
		$cacheKey,
		$cacheOffset = 1
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return Constant::$FALSE;
		}

		return $this->noSqlServerObject->increment(
			key: $cacheKey,
			offset: $cacheOffset
		);
	}

	/**
	 * Delete cache key
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	public function cacheDelete(
		$cacheKey
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return Constant::$FALSE;
		}

		return $this->noSqlServerObject->delete(
			key: $cacheKey
		);
	}
}
