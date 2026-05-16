<?php

/**
 * NoSql Container
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

/**
 * NoSql Container (Cache) Interface
 * php version 8.3
 *
 * @category  Cache_Interface
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface CacheServerInterface
{
	/**
	 * Cache Server Object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void;

	/**
	 * Cache key exist
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	public function cacheExist($cacheKey): mixed;

	/**
	 * Get cache key
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	public function cacheGet($cacheKey): mixed;

	/**
	 * Set cache key
	 *
	 * @param string $cacheKey    Cache key
	 * @param string $cacheValue  Cache value
	 * @param int    $cacheExpire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function cacheSet($cacheKey, $cacheValue, $cacheExpire = null): mixed;

	/**
	 * Increment cache key with offset
	 *
	 * @param string $cacheKey    Cache key
	 * @param int    $cacheOffset Offset
	 *
	 * @return int
	 */
	public function cacheIncrement($cacheKey, $cacheOffset = 1): int;

	/**
	 * Delete cache key
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	public function cacheDelete($cacheKey): mixed;
}
