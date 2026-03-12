<?php

/**
 * NoSql Database
 * php version 8.3
 *
 * @category  NoSql
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\NoSql;

use Microservices\App\HttpStatus;
use Microservices\App\Server\Container\NoSql\NoSqlInterface;

/**
 * Memcached
 * php version 8.3
 *
 * @category  Memcached
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
class Memcached implements NoSqlInterface
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
	 * Cache Server Object
	 *
	 * @var null|\Memcached
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

		if (!extension_loaded(extension: 'memcached')) {
			throw new \Exception(
				message: 'Unable to find Memcached extension',
				code: HttpStatus::$InternalServerError
			);
		}

		try {
			$this->cacheServerObj = new \Memcached();
			$this->cacheServerObj->addServer($this->cacheServerHostname, $this->cacheServerPort);
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

		return $this->getCache(key: $key) !== false;
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

		return $this->cacheServerObj->get($key);
	}

	/**
	 * Set cache on basis of key
	 *
	 * @param string $key    Cache key
	 * @param string $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function setCache($key, $value, $expire = null): mixed
	{
		$this->connect();

		if ($expire === null) {
			return $this->cacheServerObj->set($key, $value);
		} else {
			return $this->cacheServerObj->set($key, $value, $expire);
		}
	}

	/**
	 * Increment Key value with offset
	 *
	 * @param string $key    Cache key
	 * @param int    $offset Offset
	 *
	 * @return int
	 */
	public function incrementCache($key, $offset = 1): int
	{
		$this->connect();

		return $this->cacheServerObj->increment($key, $offset);
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

		return $this->cacheServerObj->delete($key);
	}
}
