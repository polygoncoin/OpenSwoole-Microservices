<?php

/**
 * Handling Cache via pgsql
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
use Microservices\App\Servers\Database\PgSql as DB_PgSQL;

/**
 * Caching via pgsql
 * php version 8.3
 *
 * @category  Cache_PgSQL
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PgSql extends AbstractCache
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
     * @var null|Pg_MySql
     */
    private $cache = null;

    /**
     * Current timestamp
     *
     * @var null|int
     */
    private $ts = null;

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
        $this->ts = time();
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

        try {
            $this->cache = new DB_PgSql(
                hostname: $this->hostname,
                port: $this->port,
                username: $this->username,
                password: $this->password,
                database: $this->database
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
        if ($this->database !== null) {
            $this->cache->useDatabase();
        }
    }

    /**
     * Checks if cache key exist
     *
     * @param string $key Cache key
     *
     * @return bool
     */
    public function cacheExists($key): bool
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
     * @param string   $key    Cache key
     * @param string   $value  Cache value
     * @param null|int $expire Seconds to expire. Default 0 - doesn't expire
     *
     * @return void
     */
    public function setCache($key, $value, $expire = null): void
    {
        $this->useDatabase();

        $keyDetails = $this->getKeyDetails(key: $key);

        if (isset($keyDetails['count']) && $keyDetails['count'] > 0) {
            $sql = "DELETE FROM `{$keyDetails['table']}` WHERE `key` = ?";
            $params = [$keyDetails['key']];
            $this->cache->execDbQuery($sql, $params);
            $this->cache->closeCursor();
        }

        $sql = "
            INSERT INTO `{$keyDetails['table']}`
            SET `value` = ?, `ts` = ?, `key` = ?
        ";
        if ($expire === null) {
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
            $this->cache->execDbQuery($sql, $params);
            $this->cache->closeCursor();
        }
    }

    /**
     * Get Key Details
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
        $params = [$keyDetails['key'], $this->ts];

        $this->cache->execDbQuery($sql, $params);
        $row = $this->cache->fetch();
        $this->cache->closeCursor();

        $keyDetails['count'] = $row['count'];

        return $keyDetails;
    }
}
