<?php

/**
 * Handling Cache via MongoDb
 * php version 8.3
 *
 * @category  Cache
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\CacheServer;

use Microservices\App\Server\CacheServer\CacheServerInterface;
use Microservices\App\Server\Container\NoSql\MongoDb as Cache_MongoDb;

/**
 * Caching via MongoDb
 * php version 8.3
 *
 * @category  Cache_MongoDb
 * @package   Openswoole_Microservices
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
	private $cacheServerDb = null;

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
	private $cacheServerObj = null;

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
			$this->cacheServerObj = new Cache_MongoDb(
				cacheServerHostname: $this->cacheServerHostname,
				cacheServerPort: $this->cacheServerPort,
				cacheServerUsername: $this->cacheServerUsername,
				cacheServerPassword: $this->cacheServerPassword,
				cacheServerDb: $this->cacheServerDb,
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
	public function cacheExist($cacheKey): mixed
	{
		$this->connect();

		return $this->cacheServerObj->cacheExist(cacheKey: $cacheKey);
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

		return $this->cacheServerObj->cacheGet(cacheKey: $cacheKey);
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

		return $this->cacheServerObj->cacheSet(
			cacheKey: $cacheKey,
			value: $value,
			expire: $expire
		);
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

		return $this->cacheServerObj->cacheIncrement(
			cacheKey: $cacheKey,
			offset: $offset
		);
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

		return $this->cacheServerObj->cacheDelete(cacheKey: $cacheKey);
	}
}
