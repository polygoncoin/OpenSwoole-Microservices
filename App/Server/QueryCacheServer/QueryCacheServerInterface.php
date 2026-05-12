<?php

/**
 * Query Cache
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

/**
 * Query Cache Interface
 * php version 8.3
 *
 * @category  Query_Cache_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface QueryCacheServerInterface
{
	/**
	 * Connect Query Cache
	 *
	 * @return void
	 */
	public function connect(): void;

	/**
	 * Query Cache key exist
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheExist($queryCacheKey): mixed;

	/**
	 * Get Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheGet($queryCacheKey): mixed;

	/**
	 * Set cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 * @param string $value         Query Cache value
	 *
	 * @return mixed
	 */
	public function queryCacheSet($queryCacheKey, $value): mixed;

	/**
	 * Increment Query Cache key as per offset
	 *
	 * @param string $queryCacheKey Query Cache key
	 * @param int    $offset        Query Cache offset
	 *
	 * @return mixed
	 */
	public function queryCacheIncrement($queryCacheKey, $offset = 1): mixed;

	/**
	 * Delete cache on basis of key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheDelete($queryCacheKey): mixed;
}
