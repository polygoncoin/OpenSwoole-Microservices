<?php
namespace Microservices\App\Servers\Cache;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Logs;
use Microservices\App\Servers\Cache\AbstractCache;

/**
 * Loading Redis server
 *
 * This class is built to handle cache operation.
 *
 * @category   Cache - Redis
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Redis extends AbstractCache
{
    /**
     * Cache hostname
     *
     * @var string
     */
    private $hostname = null;

    /**
     * Cache port
     *
     * @var integer
     */
    private $port = null;

    /**
     * Cache password
     *
     * @var string
     */
    private $username = null;

    /**
     * Cache password
     *
     * @var string
     */
    private $password = null;

    /**
     * Cache database
     *
     * @var string
     */
    private $database = null;

    /**
     * Cache connection
     *
     * @var object
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
        $port,
        $username,
        $password,
        $database
    )
    { 
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        if (!is_null($database)) {
            $this->database = $database;
        }
    }

    /**
     * Cache connection
     *
     * @return void
     */
    public function connect()
    {
        if (!is_null($this->cache)) return;

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

            if (!is_null($this->database)) {
                $this->useDatabase($this->database);
            }

            if (!$this->cache->ping()) {
                die('Unable to ping cache server');
                return;
            }
        } catch (\Exception $e) {
            $log = [
                'datetime' => date('Y-m-d H:i:s'),
                // 'input' => $this->c->httpRequest->input,
                'error' => 'Unable to connect to cache server'
            ];
            Logs::log('error', json_encode($log));

            die('Unable to connect to cache server');

            return;
        }
    }

    /**
     * Use Database
     *
     * @param string $database Database .env string
     * @return void
     */
    public function useDatabase($database)
    {
        $this->connect();
        $this->cache->select($this->database);
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
        return $this->cache->exists($key);
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
        return $this->cache->del($key);
    }
    
    /**
     * Checks member is present in set
     *
     * @param string $set    Cache Set
     * @param string $member Cache Set member
     * @return boolean
     */
    public function isSetMember($set, $member)
    {
        $this->connect();
        return $this->cache->sIsMember($set, $member);
    }

    /**
     * Set Set values
     *
     * @param string $key        Cache Set key
     * @param array  $valueArray Cache values for Set
     * @return void
     */
    public function setSetMembers($key, $valueArray)
    {
        $this->connect();

        $this->deleteCache($key);
        foreach ($valueArray as $value) {
            $this->cache->sAdd($key, $value);
        }
    }
}
