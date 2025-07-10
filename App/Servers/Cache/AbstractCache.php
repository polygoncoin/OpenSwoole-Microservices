<?php
/**
 * Cache
 * php version 8.3
 *
 * @category  Cache
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App\Servers\Cache;

/**
 * Cache Abstract class
 * php version 8.3
 *
 * @category  Cache_Abstract_Class
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
abstract class AbstractCache
{
    /**
     * Cache connection
     *
     * @return void
     */
    abstract public function connect(): void;

    /**
     * Use Cache Database
     *
     * @return void
     */
    abstract public function useDatabase(): void;

    /**
     * Checks if cache key exist
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    abstract public function cacheExists($key): mixed;

    /**
     * Get cache on basis of key
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    abstract public function getCache($key): mixed;

    /**
     * Set cache on basis of key
     *
     * @param string $key    Cache key
     * @param string $value  Cache value
     * @param int    $expire Seconds to expire. Default 0 - doesn't expire
     *
     * @return mixed
     */
    abstract public function setCache($key, $value, $expire = null): mixed;

    /**
     * Delete cache on basis of key
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    abstract public function deleteCache($key): mixed;
}
