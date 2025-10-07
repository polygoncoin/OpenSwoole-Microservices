<?php

/**
 * Handling Cache via Redis
 * php version 8.3
 *
 * @category  Cache
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
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
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Redis extends AbstractCache
{
    /**
     * Cache hostname
     *
     * @var null|string
     */
    private $hostname = null;

    /**
     * Cache port
     *
     * @var null|int
     */
    private $port = null;

    /**
     * Cache password
     *
     * @var null|string
     */
    private $username = null;

    /**
     * Cache password
     *
     * @var null|string
     */
    private $password = null;

    /**
     * Cache database
     *
     * @var null|string
     */
    private $database = null;

    /**
     * Cache connection
     *
     * @var null|\Redis
     */
    private $cache = null;

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
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        if ($database !== null) {
            $this->database = $database;
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
        if ($this->cache !== null) {
             return;
        }

        if (!extension_loaded(extension: 'redis')) {
            throw new \Exception(
                message: 'Unable to find Redis extension',
                code: HttpStatus::$InternalServerError
            );
        }

        try {
            // https://github.com/phpredis/phpredis?tab=readme-ov-file#class-redis
            $this->cache = new \Redis(
                [
                    'host' => $this->hostname,
                    'port' => (int)$this->port,
                    'connectTimeout' => 2.5,
                    'auth' => [$this->username, $this->password],
                ]
            );

            if ($this->database !== null) {
                $this->useDatabase();
            }

            if (!$this->cache->ping()) {
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
        if ($this->database !== null) {
            $this->cache->select($this->database);
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
        return $this->cache->exists($key);
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
        return $this->cache->get($key);
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

        if ($expire === null) {
            return $this->cache->set($key, $value);
        } else {
            return $this->cache->set($key, $value, $expire);
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
        return $this->cache->del($key);
    }
}
