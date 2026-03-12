<?php

/**
 * NoSql Container
 * php version 8.3
 *
 * @category  Cache
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\CacheServer;

/**
 * NoSql Container (Cache) Interface
 * php version 8.3
 *
 * @category  Cache_Interface
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
interface CacheInterface
{
	/**
	 * Cache Server Object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void;

	/**
	 * Checks if cache key exist
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function cacheExists($key): mixed;

	/**
	 * Get cache on basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function getCache($key): mixed;

	/**
	 * Set cache on basis of key
	 *
	 * @param string $key    Cache key
	 * @param string $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function setCache($key, $value, $expire = null): mixed;

	/**
	 * Increment Key value with offset
	 *
	 * @param string $key    Cache key
	 * @param int    $offset Offset
	 *
	 * @return int
	 */
	public function incrementCache($key, $offset = 1): int;

	/**
	 * Delete basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function deleteCache($key): mixed;
}
