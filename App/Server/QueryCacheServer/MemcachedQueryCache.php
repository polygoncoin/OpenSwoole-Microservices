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
	 * Query Cache Server Hostname
	 *
	 * @var null|string
	 */
	private $queryCacheServerHostname = null;

	/**
	 * Query Cache Server Port
	 *
	 * @var null|int
	 */
	private $queryCacheServerPort = null;

	/**
	 * Query Cache Server Username
	 *
	 * @var null|string
	 */
	private $queryCacheServerUsername = null;

	/**
	 * Query Cache Server Password
	 *
	 * @var null|string
	 */
	private $queryCacheServerPassword = null;

	/**
	 * Query Cache Server DB
	 *
	 * @var null|string
	 */
	private $queryCacheServerDB = null;

	/**
	 * Query Cache Server Table
	 *
	 * @var null|string
	 */
	public $queryCacheServerTable = null;

	/**
	 * Query Cache Server Object
	 *
	 * @var null|Cache_Memcached
	 */
	private $queryCacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string      $queryCacheServerHostname Query Cache Server Hostname
	 * @param int         $queryCacheServerPort     Query Cache Server Port
	 * @param string      $queryCacheServerUsername Query Cache Server Username
	 * @param string      $queryCacheServerPassword Query Cache Server Password
	 * @param null|string $queryCacheServerDB       Query Cache Server Database
	 * @param null|string $queryCacheServerTable    Query Cache Server Table
	 */
	public function __construct(
		$queryCacheServerHostname,
		$queryCacheServerPort,
		$queryCacheServerUsername,
		$queryCacheServerPassword,
		$queryCacheServerDB,
		$queryCacheServerTable
	) {
		$this->queryCacheServerHostname = $queryCacheServerHostname;
		$this->queryCacheServerPort = $queryCacheServerPort;
		$this->queryCacheServerUsername = $queryCacheServerUsername;
		$this->queryCacheServerPassword = $queryCacheServerPassword;
		$this->queryCacheServerDB = $queryCacheServerDB;
		$this->queryCacheServerTable = $queryCacheServerTable;
	}

	/**
	 * Query Cache Server Object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void
	{
		if ($this->queryCacheServerObj !== null) {
			return;
		}

		try {
			$this->queryCacheServerObj = new Cache_Memcached(
				queryCacheServerHostname: $this->queryCacheServerHostname,
				queryCacheServerPort: $this->queryCacheServerPort,
				queryCacheServerUsername: $this->queryCacheServerUsername,
				queryCacheServerPassword: $this->queryCacheServerPassword,
				queryCacheServerDB: $this->queryCacheServerDB,
				queryCacheServerTable: $this->queryCacheServerTable
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

		return $this->queryCacheServerObj->cacheExists(key: $key);
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

		return $this->queryCacheServerObj->getCache($key);
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

		return $this->queryCacheServerObj->setCache($key, $value);
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

		return $this->queryCacheServerObj->deleteCache($key);
	}
}
