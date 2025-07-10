<?php
/**
 * Handling Cache via Memcached
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

use Microservices\App\HttpStatus;
use Microservices\App\Servers\Cache\AbstractCache;

/**
 * Caching via Memcached
 * php version 8.3
 *
 * @category  Cache_Memcached
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Memcached extends AbstractCache
{
    /**
     * Cache hostname
     *
     * @var null|string
     */
    private $_hostname = null;

    /**
     * Cache port
     *
     * @var null|int
     */
    private $_port = null;

    /**
     * Cache connection
     *
     * @var null|\Memcached
     */
    private $_cache = null;

    /**
     * Cache connection
     *
     * @param string $hostname Hostname .env string
     * @param string $port     Port .env string
     */
    public function __construct($hostname, $port)
    {
        $this->_hostname = $hostname;
        $this->_port = $port;
    }

    /**
     * Cache connection
     *
     * @return void
     * @throws \Exception
     */
    public function connect(): void
    {
        if (!is_null(value: $this->_cache)) {
            return;
        }

        if (!extension_loaded(extension: 'memcached')) {
            throw new \Exception(
                message: "Unable to find Memcached extension",
                code: HttpStatus::$InternalServerError
            );
        }

        try {
            $this->_cache = new \Memcached();
            $this->_cache->addServer($this->_hostname, $this->_port);
        } catch (\Exception $e) {
            throw new \Exception(
                message: $e->getMessage(),
                code: HttpStatus::$InternalServerError
            );
        }
    }

    /**
     * Use Database
     *
     * @return void
     * @throws \Exception
     */
    public function useDatabase(): void
    {
        throw new \Exception(
            message: 'No database support',
            code: HttpStatus::$InternalServerError
        );
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
        return $this->_cache->get($key);
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

        if (is_null(value: $expire)) {
            return $this->_cache->set($key, $value);
        } else {
            return $this->_cache->set($key, $value, $expire);
        }
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
        return $this->_cache->delete($key);
    }
}
