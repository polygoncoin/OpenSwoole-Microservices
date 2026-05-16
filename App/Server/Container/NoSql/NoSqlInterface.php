<?php

/**
 * NoSql Container
 * php version 8.3
 *
 * @category  NoSqlContainers
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\NoSql;

/**
 * NoSql Interface
 * php version 8.3
 *
 * @category  NoSql_Interface
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface NoSqlInterface
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
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function exist($key): mixed;

	/**
	 * Get cache key
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function get($key): mixed;

	/**
	 * Set cache key
	 *
	 * @param string $key    Key
	 * @param string $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function set($key, $value, $expire = null): mixed;

	/**
	 * Increment cache key with offset
	 *
	 * @param string $key    Key
	 * @param int    $offset Offset
	 *
	 * @return int
	 */
	public function increment($key, $offset = 1): int;

	/**
	 * Delete cache key
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function delete($key): mixed;
}
