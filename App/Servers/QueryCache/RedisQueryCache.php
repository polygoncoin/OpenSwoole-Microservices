<?php

/**
 * Handling Query Cache via Redis
 * php version 8.3
 *
 * @category  QueryCache
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Servers\QueryCache;

use Microservices\App\HttpStatus;
use Microservices\App\Servers\QueryCache\QueryCacheInterface;
use Microservices\App\Servers\Containers\NoSql\Redis as Cache_Redis;

/**
 * Caching via Redis
 * php version 8.3
 *
 * @category  QueryCache_Redis
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RedisQueryCache implements QueryCacheInterface
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
     * Cache collection
     *
     * @var null|string
     */
    public $table = null;

    /**
     * Cache connection
     *
     * @var null|Cache_Redis
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
     * @param string $table    Table .env string
     */
    public function __construct(
        $hostname,
        $port,
        $username,
        $password,
        $database,
        $table
    ) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->table = $table;
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

        try {
            $this->cache = new Cache_Redis(
                hostname: $this->hostname,
                port: $this->port,
                username: $this->username,
                password: $this->password,
                database: $this->database,
                table: $this->table
            );
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

        return $this->cache->cacheExists(key: $key);
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

        return $this->cache->getCache($key);
    }

    /**
     * Set cache on basis of key
     *
     * @param string $key    Cache key
     * @param string $value  Cache value
     *
     * @return mixed
     */
    public function setCache($key, $value): mixed
    {
        $this->connect();

        return $this->cache->setCache($key, $value);
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

        return $this->cache->deleteCache($key);
    }
}
