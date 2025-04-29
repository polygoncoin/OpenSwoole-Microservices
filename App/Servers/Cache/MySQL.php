<?php
namespace Microservices\App\Servers\Cache;

use Microservices\App\HttpStatus;
use Microservices\App\Servers\Cache\AbstractCache;
use Microservices\App\Servers\Database\MySql as DB_MySql;

/**
 * Loading MySql server
 *
 * This class is built to handle cache operation
 *
 * @category   Cache - MySql
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class MySql extends AbstractCache
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
     * @var null|DB_MySql
     */
    private $cache = null;

    /**
     * Current timestamp
     *
     * @var null|integer
     */
    private $ts = null;

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
        $this->ts = time();
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
     * @throws \Exception
     */
    public function connect()
    {
        if (!is_null($this->cache)) return;
        try {
            $this->cache = new DB_MySql(
                $this->hostname,
                $this->port,
                $this->username,
                $this->password,
                $this->database
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), HttpStatus::$InternalServerError);
        }
    }

    /**
     * Use Database
     *
     * @return void
     */
    public function useDatabase()
    {
        $this->connect();
        if (!is_null($this->database)) {
            $this->cache->useDatabase();
        }
    }

    /**
     * Checks if cache key exist
     *
     * @param string $key Cache key
     * @return boolean
     */
    public function cacheExists($key)
    {
        $this->useDatabase();
        $keyDetails = $this->getKeyDetails($key);
        return $keyDetails['count'] === 1;
    }

    /**
     * Get cache on basis of key
     *
     * @param string $key Cache key
     * @return string
     */
    public function getCache($key)
    {
        $this->useDatabase();

        $keyDetails = $this->getKeyDetails($key);

        if (isset($keyDetails['count']) && $keyDetails['count'] === 1) {
            $sql = "SELECT `value` FROM `{$keyDetails['table']}` WHERE `key` = ? AND (`ts` = 0 OR `ts` > ?)";
            $params = [$keyDetails['key'], $this->ts];
            $this->cache->execDbQuery($sql, $params);
            $row = $this->cache->fetch();
            $this->cache->closeCursor();
            return $row['value'];
        } else {
            return false;
        }
    }

    /**
     * Set cache on basis of key
     *
     * @param string  $key    Cache key
     * @param string  $value  Cache value
     * @param null|integer $expire Seconds to expire. Default 0 - doesnt expire
     * @return integer
     */
    public function setCache($key, $value, $expire = null)
    {
        $this->useDatabase();

        $keyDetails = $this->getKeyDetails($key);

        if (isset($keyDetails['count']) && $keyDetails['count'] > 0) {
            $sql = "DELETE FROM `{$keyDetails['table']}` WHERE `key` = ?";
            $params = [$keyDetails['key']];
            $this->cache->execDbQuery($sql, $params);
            $this->cache->closeCursor();
        }

        $sql = "INSERT INTO `{$keyDetails['table']}` SET `value` = ?, `ts` = ?, `key` = ?";
        if (is_null($expire)) {
            $params = [$value, 0, $keyDetails['key']];
        } else {
            $params = [$value, $this->ts + $expire, $keyDetails['key']];
        }

        $this->cache->execDbQuery($sql, $params);
        $this->cache->closeCursor();
    }

    /**
     * Delete basis of key
     *
     * @param string $key Cache key
     * @return integer
     */
    public function deleteCache($key)
    {
        $this->useDatabase();

        $keyDetails = $this->getKeyDetails($key);

        if (isset($keyDetails['count']) && $keyDetails['count'] > 0) {
            $sql = "DELETE FROM `{$keyDetails['table']}` WHERE `key` = ?";
            $params = [$keyDetails['key']];
            $this->cache->execDbQuery($sql, $params);
            $this->cache->closeCursor();
        }
    }

    public function getKeyDetails($key)
    {
        $pos = strpos($key, ':');
        $tableKey = substr($key, 0, $pos);

        switch ($tableKey) {
            case 'c':
                $table = 'client';
                break;
            case 'cu':
                $table = 'user';
                break;
            case 'g':
                $table = 'group';
                break;
            case 'cidr':
                $table = 'cidr';
                break;
            case 'ut':
                $table = 'usertoken';
                break;
            case 't':
                $table = 'token';
                break;
        }

        $keyDetails = [
            'table' => $table,
            'key' => $key
        ];

        $sql = "SELECT count(1) as `count` FROM `{$keyDetails['table']}` WHERE `key` = ? AND (`ts` = 0 OR `ts` > ?)";
        $params = [$keyDetails['key'], $this->ts];

        $this->cache->execDbQuery($sql, $params);
        $row = $this->cache->fetch();
        $this->cache->closeCursor();

        $keyDetails['count'] = $row['count'];

        return $keyDetails;
    }
}
