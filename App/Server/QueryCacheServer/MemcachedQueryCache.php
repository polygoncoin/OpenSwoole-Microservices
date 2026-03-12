<?php

/**
 * Handling Query Cache via Memcached
 * php version 8.3
 *
 * @category  QueryCache
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\QueryCacheServer;

use Microservices\App\HttpStatus;
use Microservices\App\Server\QueryCacheServer\QueryCacheInterface;
use Microservices\App\Server\Container\NoSql\Memcached as Cache_Memcached;

/**
 * Query Caching via Memcached
 * php version 8.3
 *
 * @category  QueryCache_Memcached
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
class MemcachedQueryCache implements QueryCacheInterface
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
	private $cacheServerDB = null;

	/**
	 * Cache Server Table
	 *
	 * @var null|string
	 */
	public $cacheServerTable = null;

	/**
	 * Cache Server Object
	 *
	 * @var null|Cache_Memcached
	 */
	private $cacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string $cacheServerHostname Hostname
	 * @param string $cacheServerPort     Port
	 * @param string $cacheServerUsername Username
	 * @param string $cacheServerPassword Password
	 * @param string $cacheServerDB       Database
	 * @param string $cacheServerTable    Table
	 */
	public function __construct(
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDB,
		$cacheServerTable
	) {
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDB = $cacheServerDB;
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
			$this->cacheServerObj = new Cache_Memcached(
				cacheServerHostname: $this->cacheServerHostname,
				cacheServerPort: $this->cacheServerPort,
				cacheServerUsername: $this->cacheServerUsername,
				cacheServerPassword: $this->cacheServerPassword,
				cacheServerDB: $this->cacheServerDB,
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
	 * Checks if cache key exist
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function cacheExists($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->cacheExists(key: $key);
	}

	/**
	 * Get cache on basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function getCache($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->getCache($key);
	}

	/**
	 * Set cache on basis of key
	 *
	 * @param string $key    Cache key
	 * @param string $value  Cache value
	 *
	 * @return mixed
	 */
	public function setCache($key, $value): mixed
	{
		$this->connect();

		return $this->cacheServerObj->setCache($key, $value);
	}

	/**
	 * Delete basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function deleteCache($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->deleteCache($key);
	}
}
