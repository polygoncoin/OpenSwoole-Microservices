<?php

/**
 * Handling Query Cache via PostgreSql
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
use Microservices\App\Servers\Containers\Sql\PostgreSql as DB_PostgreSql;

/**
 * Query Caching via PostgreSql
 * php version 8.3
 *
 * @category  QueryCache_PostgreSql
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlQueryCache implements QueryCacheInterface
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
     * Cache table
     *
     * @var null|string
     */
    private $table = null;

    /**
     * Cache connection
     *
     * @var null|DB_PostgreSql
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
            $this->cache = new DB_PostgreSql(
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
     * Checks if cache key exist
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    public function cacheExists($key): mixed
    {
        $this->connect();

        $sql = "
            SELECT count(1) as count
            FROM {$this->table}
            WHERE key = :key
        ";
        $params = [':key' => $key];

        $this->cache->execDbQuery(sql: $sql, params: $params);
        $row = $this->cache->fetch();
        $this->cache->closeCursor();

        return $row['count'] === 1;
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

        $sql = "
            SELECT value
            FROM {$this->table}
            WHERE key = :key
        ";
        $params = [':key' => $key];
        $this->cache->execDbQuery(sql: $sql, params: $params);
        if ($row = $this->cache->fetch()) {
            $this->cache->closeCursor();
            return $row['value'];
        }
        $this->cache->closeCursor();
        return false;
    }

    /**
     * Set cache on basis of key
     *
     * @param string   $key    Cache key
     * @param string   $value  Cache value
     *
     * @return mixed
     */
    public function setCache($key, $value): mixed
    {
        $this->connect();
        $this->deleteCache($key);

        $sql = "
            INSERT INTO {$this->table}
            SET key = :value, value = :value
        ";
        $params = [':key' => $key, ':value' => $value];

        $this->cache->execDbQuery(sql: $sql, params: $params);
        $this->cache->closeCursor();

        return true;
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

        $sql = "DELETE FROM {$this->table} WHERE key = :key";
        $params = [':key' => $key];
        $this->cache->execDbQuery(sql: $sql, params: $params);
        $this->cache->closeCursor();

        return true;
    }
}
