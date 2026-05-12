<?php

/**
 * Handling Query Cache via Redis
 * php version 8.3
 *
 * @category  QueryCache
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\QueryCacheServer;

use Microservices\App\HttpStatus;
use Microservices\App\Server\QueryCacheServer\QueryCacheServerInterface;
use Microservices\App\Server\Container\NoSql\Redis as QueryCache_Redis;

/**
 * Caching via Redis
 * php version 8.3
 *
 * @category  QueryCache_Redis
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RedisQueryCache implements QueryCacheServerInterface
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
	private $queryCacheServerDb = null;

	/**
	 * Cache collection
	 *
	 * @var null|string
	 */
	public $queryCacheServerTable = null;

	/**
	 * Query Cache Server Object
	 *
	 * @var null|QueryCache_Redis
	 */
	private $queryCacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string      $queryCacheServerHostname Query Cache Server Hostname
	 * @param int         $queryCacheServerPort     Query Cache Server Port
	 * @param string      $queryCacheServerUsername Query Cache Server Username
	 * @param string      $queryCacheServerPassword Query Cache Server Password
	 * @param null|string $queryCacheServerDb       Query Cache Server Database
	 * @param null|string $queryCacheServerTable    Query Cache Server Table
	 */
	public function __construct(
		$queryCacheServerHostname,
		$queryCacheServerPort,
		$queryCacheServerUsername,
		$queryCacheServerPassword,
		$queryCacheServerDb,
		$queryCacheServerTable
	) {
		$this->queryCacheServerHostname = $queryCacheServerHostname;
		$this->queryCacheServerPort = $queryCacheServerPort;
		$this->queryCacheServerUsername = $queryCacheServerUsername;
		$this->queryCacheServerPassword = $queryCacheServerPassword;
		$this->queryCacheServerDb = $queryCacheServerDb;
		$this->queryCacheServerTable = $queryCacheServerTable;
	}

	/**
	 * Connect Query Cache
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
			$this->queryCacheServerObj = new QueryCache_Redis(
				cacheServerHostname: $this->queryCacheServerHostname,
				cacheServerPort: $this->queryCacheServerPort,
				cacheServerUsername: $this->queryCacheServerUsername,
				cacheServerPassword: $this->queryCacheServerPassword,
				cacheServerDb: $this->queryCacheServerDb,
				cacheServerTable: $this->queryCacheServerTable
			);
		} catch (\Exception $e) {
			throw new \Exception(
				message: $e->getMessage(),
				code: HttpStatus::$InternalServerError
			);
		}
	}

	/**
	 * Query Cache key exist
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheExist($queryCacheKey): mixed
	{
		$this->connect();

		return $this->queryCacheServerObj->cacheExist(cacheKey: $queryCacheKey);
	}

	/**
	 * Get Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheGet($queryCacheKey): mixed
	{
		$this->connect();

		return $this->queryCacheServerObj->cacheGet($queryCacheKey);
	}

	/**
	 * Set cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 * @param string $value         Query Cache value
	 *
	 * @return mixed
	 */
	public function queryCacheSet($queryCacheKey, $value): mixed
	{
		$this->connect();

		return $this->queryCacheServerObj->cacheSet($queryCacheKey, $value);
	}

	/**
	 * Increment Query Cache key as per offset
	 *
	 * @param string $queryCacheKey Query Cache key
	 * @param int    $offset        Query Cache offset
	 *
	 * @return mixed
	 */
	public function queryCacheIncrement($queryCacheKey, $offset = 1): mixed
	{
		$this->connect();

		return $this->queryCacheServerObj->cacheIncrement($queryCacheKey, $offset);
	}

	/**
	 * Delete Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheDelete($queryCacheKey): mixed
	{
		$this->connect();

		return $this->queryCacheServerObj->cacheDelete($queryCacheKey);
	}
}
