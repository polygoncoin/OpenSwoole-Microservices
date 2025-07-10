<?php
/**
 * Handling Cache via MySQL
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
use Microservices\App\Servers\Database\MySql as DB_MySql;

/**
 * Caching via MySQL
 * php version 8.3
 *
 * @category  Cache_MySQL
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MySql extends AbstractCache
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
     * @var null|DB_MySql
     */
    private $_cache = null;

    /**
     * Current timestamp
     *
     * @var null|int
     */
    private $_ts = null;

    /**
     * Cache connection
     *
     * @param string $hostname Hostname .env string
     * @param int    $port     Port .env string
     * @param string $username Username .env string
     * @param string $password Password .env string
     * @param string $database Database .env string
     */
    public function __construct($hostname, $port, $username, $password, $database)
    {
        $this->_ts = time();
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

        try {
            $this->_cache = new DB_MySql(
                hostname: $this->_hostname, 
                port: $this->_port, 
                username: $this->_username, 
                password: $this->_password, 
                database: $this->_database
            );
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
            $this->_cache->useDatabase();
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
        $keyDetails = $this->getKeyDetails(key: $key);
        return $keyDetails['count'] === 1;
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

        $keyDetails = $this->getKeyDetails(key: $key);

        if (isset($keyDetails['count']) && $keyDetails['count'] === 1) {
            $sql = "
                SELECT `value` 
                FROM `{$keyDetails['table']}` 
                WHERE `key` = ? AND (`ts` = 0 OR `ts` > ?)
            ";
            $params = [$keyDetails['key'], $this->_ts];
            $this->_cache->execDbQuery(sql: $sql, params: $params);
            $row = $this->_cache->fetch();
            $this->_cache->closeCursor();
            return $row['value'];
        } else {
            return false;
        }
    }

    /**
     * Set cache on basis of key
     *
     * @param string   $key    Cache key
     * @param string   $value  Cache value
     * @param null|int $expire Seconds to expire. Default 0 - doesn't expire
     *
     * @return mixed
     */
    public function setCache($key, $value, $expire = null): mixed
    {
        $this->useDatabase();

        $keyDetails = $this->getKeyDetails(key: $key);

        if (isset($keyDetails['count']) && $keyDetails['count'] > 0) {
            $sql = "DELETE FROM `{$keyDetails['table']}` WHERE `key` = ?";
            $params = [$keyDetails['key']];
            $this->_cache->execDbQuery(sql: $sql, params: $params);
            $this->_cache->closeCursor();
        }

        $sql = "
            INSERT INTO `{$keyDetails['table']}` 
            SET `value` = ?, `ts` = ?, `key` = ?
        ";
        if (is_null(value: $expire)) {
            $params = [$value, 0, $keyDetails['key']];
        } else {
            $params = [$value, $this->_ts + $expire, $keyDetails['key']];
        }

        $this->_cache->execDbQuery(sql: $sql, params: $params);
        $this->_cache->closeCursor();

        return true;
    }

    /**
     * Delete basis of key
     *
     * @param string $key Cache key
     *
     * @return void
     */
    public function deleteCache($key): void
    {
        $this->useDatabase();

        $keyDetails = $this->getKeyDetails(key: $key);

        if (isset($keyDetails['count']) && $keyDetails['count'] > 0) {
            $sql = "DELETE FROM `{$keyDetails['table']}` WHERE `key` = ?";
            $params = [$keyDetails['key']];
            $this->_cache->execDbQuery(sql: $sql, params: $params);
            $this->_cache->closeCursor();
        }
    }

    /**
     * Get Details of key
     *
     * @param string $key Cache key
     *
     * @return array
     */
    public function getKeyDetails($key): array
    {
        $pos = strpos(haystack: $key, needle: ':');
        $tableKey = substr(string: $key, offset: 0, length: $pos);

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

        $sql = "
            SELECT count(1) as `count` 
            FROM `{$keyDetails['table']}` 
            WHERE `key` = ? AND (`ts` = 0 OR `ts` > ?)
        ";
        $params = [$keyDetails['key'], $this->_ts];

        $this->_cache->execDbQuery(sql: $sql, params: $params);
        $row = $this->_cache->fetch();
        $this->_cache->closeCursor();

        $keyDetails['count'] = $row['count'];

        return $keyDetails;
    }
}
