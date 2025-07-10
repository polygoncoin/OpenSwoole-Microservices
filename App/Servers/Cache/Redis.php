<?php
/**
 * Handling Cache via Redis
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
 * Caching via Redis
 * php version 8.3
 *
 * @category  Cache_Redis
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Redis extends AbstractCache
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
     * Cache password
     *
     * @var null|string
     */
    private $_username = null;

    /**
     * Cache password
     *
     * @var null|string
     */
    private $_password = null;

    /**
     * Cache database
     *
     * @var null|string
     */
    private $_database = null;

    /**
     * Cache connection
     *
     * @var null|\Redis
     */
    private $_cache = null;

    /**
     * Cache connection
     *
     * @param string $hostname Hostname .env string
     * @param string $port     Port .env string
     * @param string $username Username .env string
     * @param string $password Password .env string
     * @param string $database Database .env string
     */
    public function __construct($hostname, $port, $username, $password, $database)
    {
        $this->_hostname = $hostname;
        $this->_port = $port;
        $this->_username = $username;
        $this->_password = $password;

        if (!is_null(value: $database)) {
            $this->_database = $database;
        }
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

        if (!extension_loaded(extension: 'redis')) {
            throw new \Exception(
                message: "Unable to find Redis extension",
                code: HttpStatus::$InternalServerError
            );
        }

        try {
            // https://github.com/phpredis/phpredis?tab=readme-ov-file#class-redis
            $this->_cache = new \Redis(
                [
                    'host' => $this->_hostname, 
                    'port' => (int)$this->_port, 
                    'connectTimeout' => 2.5, 
                    'auth' => [$this->_username, $this->_password], 
                ]
            );

            if (!is_null(value: $this->_database)) {
                $this->useDatabase();
            }

            if (!$this->_cache->ping()) {
                throw new \Exception(
                    message: 'Unable to ping cache',
                    code: HttpStatus::$InternalServerError
                );
            }
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
     */
    public function useDatabase(): void
    {
        $this->connect();
        if (!is_null(value: $this->_database)) {
            $this->_cache->select($this->_database);
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
        $this->useDatabase();
        return $this->_cache->exists($key);
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
        $this->useDatabase();
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
        $this->useDatabase();

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
        $this->useDatabase();
        return $this->_cache->del($key);
    }
}
