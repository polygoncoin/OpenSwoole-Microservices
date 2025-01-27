<?php
namespace Microservices\App\Servers\Cache;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Servers\Cache\AbstractCache;

/**
 * Loading Redis server
 *
 * This class is built to handle cache operation.
 *
 * @category   Cache - Memcached
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Memcached extends AbstractCache
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
     * @var null|integer
     */
    private $port = null;

    /**
     * Cache connection
     *
     * @var null|\Redis
     */
    private $cache = null;

    /**
     * Cache connection
     *
     * @param string $hostname  Hostname .env string
     * @param string $port      Port .env string
     * @param string $password  Password .env string
     * @param string $database  Database .env string
     * @return void
     */
    public function __construct(
        $hostname,
        $port
    )
    {
        $this->hostname = $hostname;
        $this->port = $port;
    }

    /**
     * Cache connection
     *
     * @return void
     * @throws \Exception
     */
    public function connect()
    {
        if (!is_null($this->cache)) return;

        try {
            $this->cache = new \Memcached();
            $this->cache->addServer($this->hostname, $this->port);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), HttpStatus::$InternalServerError);
        }
    }

    /**
     * Use Database
     *
     * @return void
     * @throws \Exception
     */
    public function useDatabase()
    {
        throw new \Exception('No database support', HttpStatus::$InternalServerError);
    }

    /**
     * Checks if cache key exist
     *
     * @param string $key Cache key
     * @return boolean
     */
    public function cacheExists($key)
    {
        $this->connect();
        return $this->getCache($key) !== false;
    }

    /**
     * Get cache on basis of key
     *
     * @param string $key Cache key
     * @return string
     */
    public function getCache($key)
    {
        $this->connect();
        return $this->cache->get($key);
    }

    /**
     * Set cache on basis of key
     *
     * @param string  $key    Cache key
     * @param string  $value  Cache value
     * @param integer $expire Seconds to expire. Default 0 - doesnt expire
     * @return integer
     */
    public function setCache($key, $value, $expire = null)
    {
        $this->connect();

        if (is_null($expire)) {
            return $this->cache->set($key, $value);
        } else {
            return $this->cache->set($key, $value, $expire);
        }
    }

    /**
     * Delete basis of key
     *
     * @param string $key Cache key
     * @return integer
     */
    public function deleteCache($key)
    {
        $this->connect();
        return $this->cache->delete($key);
    }
}
