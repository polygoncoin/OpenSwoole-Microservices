<?php

/**
 * DB Functions
 * php version 8.3
 *
 * @category  DbFunctions
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseOpenCacheKey;
use Microservices\App\HttpStatus;

/**
 * DB Functions
 * php version 8.3
 *
 * @category  DbFunctions
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DbFunctions
{
    /**
     * Query Cache Connection Object
     *
     * @var null|Object
     */
    private static $queryCache = null;

    /** Database Connection */
    /**
     * Global
     *
     * @var null|Object
     */
    public static $globalDb = null;

    /**
     * Client Master
     *
     * @var null|Object
     */
    public static $masterDb = null;

    /**
     * Client Slave
     *
     * @var null|Object
     */
    public static $slaveDb = null;

    /** Cache Connection */
    /**
     * Global
     *
     * @var null|Object
     */
    public static $globalCache = null;

    /**
     * Client Master
     *
     * @var null|Object
     */
    public static $masterCache = null;

    /**
     * Client Slave
     *
     * @var null|Object
     */
    public static $slaveCache = null;

    /**
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     *
     * @return void
     */
    public static function connectQueryCache(): void
    {
        if (self::$queryCache !== null) {
            return;
        }

        $cacheType = getenv(name: 'queryCacheType');
        if (!in_array($cacheType, ['Redis', 'Memcached', 'MongoDb', 'MySql', 'PostgreSql'])) {
            throw new \Exception(
                message: 'Invalid query cache type',
                code: HttpStatus::$InternalServerError
            );
        }

        $queryCacheNS = 'Microservices\\App\\Servers\\QueryCache\\' . $cacheType . 'QueryCache';
        self::$queryCache = new $queryCacheNS(
            getenv(name: 'queryCacheHostname'),
            getenv(name: 'queryCachePort'),
            getenv(name: 'queryCacheUsername'),
            getenv(name: 'queryCachePassword'),
            getenv(name: 'queryCacheDatabase'),
            getenv(name: 'queryCacheTable')
        );
    }

    /**
     * Set Cache
     *
     * @param string $cacheType     Cache type
     * @param string $cacheHostname Hostname
     * @param int    $cachePort     Port
     * @param string $cacheUsername Username
     * @param string $cachePassword Password
     * @param string $cacheDatabase Database
     * @param string $cacheTable    Table
     *
     * @return object
     */
    public static function connectCache(
        $cacheType,
        $cacheHostname,
        $cachePort,
        $cacheUsername,
        $cachePassword,
        $cacheDatabase,
        $cacheTable
    ): object {
        if (!in_array($cacheType, ['Redis', 'Memcached', 'MongoDb'])) {
            throw new \Exception(
                message: 'Invalid Cache type',
                code: HttpStatus::$InternalServerError
            );
        }
        $cacheNS = 'Microservices\\App\\Servers\\Cache\\' . $cacheType . 'Cache';
        return new $cacheNS(
            $cacheHostname,
            $cachePort,
            $cacheUsername,
            $cachePassword,
            $cacheDatabase,
            $cacheTable
        );
    }

    /**
     * Initialize Global DB Connection
     *
     * @return void
     */
    public static function connectGlobalCache(): void
    {
        if (self::$globalCache !== null) {
            return;
        }
        self::$globalCache = self::connectCache(
            cacheType: getenv(name: 'globalCacheType'),
            cacheHostname: getenv(name: 'globalCacheHostname'),
            cachePort: getenv(name: 'globalCachePort'),
            cacheUsername: getenv(name: 'globalCacheUsername'),
            cachePassword: getenv(name: 'globalCachePassword'),
            cacheDatabase: getenv(name: 'globalCacheDatabase'),
            cacheTable: getenv(name: 'globalCacheTable')
        );
    }

    /**
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     *
     * @return void
     * @throws \Exception
     */
    public static function setCacheConnection($fetchFrom): void
    {
        if (Common::$req->s['cDetails'] === null) {
            throw new \Exception(
                message: 'Yet to set connection params',
                code: HttpStatus::$InternalServerError
            );
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                if (self::$masterCache !== null) {
                    return;
                }

                $masterCacheDetails = self::getCacheMasterDetails();
                self::$masterCache = self::connectCache(
                    cacheType: $masterCacheDetails['cacheType'],
                    cacheHostname: $masterCacheDetails['cacheHostname'],
                    cachePort: $masterCacheDetails['cachePort'],
                    cacheUsername: $masterCacheDetails['cacheUsername'],
                    cachePassword: $masterCacheDetails['cachePassword'],
                    cacheDatabase: $masterCacheDetails['cacheDatabase'],
                    cacheTable: $masterCacheDetails['cacheTable']
                );
                break;
            case 'Slave':
                if (self::$slaveCache !== null) {
                    return;
                }

                $slaveCacheDetails = self::getCacheSlaveDetails();
                self::$slaveCache = self::connectCache(
                    cacheType: $slaveCacheDetails['cacheType'],
                    cacheHostname: $slaveCacheDetails['cacheHostname'],
                    cachePort: $slaveCacheDetails['cachePort'],
                    cacheUsername: $slaveCacheDetails['cacheUsername'],
                    cachePassword: $slaveCacheDetails['cachePassword'],
                    cacheDatabase: $slaveCacheDetails['cacheDatabase'],
                    cacheTable: $slaveCacheDetails['cacheTable']
                );
                break;
            default:
                throw new \Exception(
                    message: "Invalid fetchFrom value '{$fetchFrom}'",
                    code: HttpStatus::$InternalServerError
                );
        }

        return;
    }

    /**
     * Set DB
     *
     * @param string $dbType     Cache type
     * @param string $dbHostname Hostname
     * @param int    $dbPort     Port
     * @param string $dbUsername Username
     * @param string $dbPassword Password
     * @param string $dbDatabase Database
     *
     * @return object
     */
    public static function connectDb(
        $dbType,
        $dbHostname,
        $dbPort,
        $dbUsername,
        $dbPassword,
        $dbDatabase
    ): object {
        if (!in_array($dbType, ['MySql', 'PostgreSql'])) {
            throw new \Exception(
                message: 'Invalid Database type',
                code: HttpStatus::$InternalServerError
            );
        }
        $dbNS = 'Microservices\\App\\Servers\\Database\\' . $dbType . 'Database';
        return new $dbNS(
            $dbHostname,
            $dbPort,
            $dbUsername,
            $dbPassword,
            $dbDatabase
        );
    }

    /**
     * Initialize Global DB Connection
     *
     * @return void
     */
    public static function connectGlobalDb(): void
    {
        if (self::$globalDb !== null) {
            return;
        }
        self::$globalDb = self::connectDb(
            dbType: getenv(name: 'globalDbType'),
            dbHostname: getenv(name: 'globalDbHostname'),
            dbPort: getenv(name: 'globalDbPort'),
            dbUsername: getenv(name: 'globalDbUsername'),
            dbPassword: getenv(name: 'globalDbPassword'),
            dbDatabase: getenv(name: 'globalDbDatabase')
        );
    }

    /**
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     *
     * @return void
     * @throws \Exception
     */
    public static function setDbConnection($fetchFrom): void
    {
        if (Common::$req->s['cDetails'] === null) {
            throw new \Exception(
                message: 'Yet to set connection params',
                code: HttpStatus::$InternalServerError
            );
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                if (self::$masterDb !== null) {
                    return;
                }

                $masterDbDetails = self::getDbMasterDetails();
                self::$masterDb = self::connectDb(
                    dbType: $masterDbDetails['dbType'],
                    dbHostname: $masterDbDetails['dbHostname'],
                    dbPort: $masterDbDetails['dbPort'],
                    dbUsername: $masterDbDetails['dbUsername'],
                    dbPassword: $masterDbDetails['dbPassword'],
                    dbDatabase: $masterDbDetails['dbDatabase']
                );
                break;
            case 'Slave':
                if (self::$slaveDb !== null) {
                    return;
                }

                $slaveDbDetails = self::getDbSlaveDetails();
                self::$slaveDb = self::connectDb(
                    dbType: $slaveDbDetails['dbType'],
                    dbHostname: $slaveDbDetails['dbHostname'],
                    dbPort: $slaveDbDetails['dbPort'],
                    dbUsername: $slaveDbDetails['dbUsername'],
                    dbPassword: $slaveDbDetails['dbPassword'],
                    dbDatabase: $slaveDbDetails['dbDatabase']
                );
                break;
            default:
                throw new \Exception(
                    message: "Invalid fetchFrom value '{$fetchFrom}'",
                    code: HttpStatus::$InternalServerError
                );
        }

        return;
    }

    /**
     * Set Cache prefix key
     *
     * @return void
     */
    public static function setDatabaseCacheKey(): void
    {
        if (Common::$req->open) {
            DatabaseOpenCacheKey::init(cID: Common::$req->s['cDetails']['id']);
        } else {
            DatabaseCacheKey::init(
                cID: Common::$req->s['cDetails']['id'],
                gID: Common::$req->s['gDetails']['id'],
                uID: Common::$req->s['uDetails']['id']
            );
        }
    }

    /**
     * Get Query cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return mixed
     */
    public static function getQueryCache($cacheKey): mixed
    {
        self::connectQueryCache();

        $json = null;
        if (self::$queryCache->cacheExists(key: $cacheKey)) {
            $json = self::$queryCache->getCache(key: $cacheKey);
        }

        return $json;
    }

    /**
     * Set Query cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     * @param string $json     JSON
     *
     * @return void
     */
    public static function setQueryCache($cacheKey, &$json): void
    {
        self::connectQueryCache();

        self::$queryCache->setCache(key: $cacheKey, value: $json);
    }

    /**
     * Delete Query Cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return void
     */
    public static function delQueryCache($cacheKey): void
    {
        self::connectQueryCache();

        self::$queryCache->deleteCache(key: $cacheKey);
    }

    /**
     * Returns Cache Master Server Details
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return array
     */
    public static function getCacheMasterDetails(): array
    {
        return [
            'cacheType' => getenv(name: Common::$req->s['cDetails']['master_cache_server_type']),
            'cacheHostname' => getenv(name: Common::$req->s['cDetails']['master_cache_hostname']),
            'cachePort' => getenv(name: Common::$req->s['cDetails']['master_cache_port']),
            'cacheUsername' => getenv(name: Common::$req->s['cDetails']['master_cache_username']),
            'cachePassword' => getenv(name: Common::$req->s['cDetails']['master_cache_password']),
            'cacheDatabase' => getenv(name: Common::$req->s['cDetails']['master_cache_database']),
            'cacheTable' => getenv(name: Common::$req->s['cDetails']['master_cache_table'])
        ];
    }

    /**
     * Returns Cache Slave Server Details
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return array
     */
    public static function getCacheSlaveDetails(): array
    {
        return [
            'cacheType' => getenv(name: Common::$req->s['cDetails']['slave_cache_server_type']),
            'cacheHostname' => getenv(name: Common::$req->s['cDetails']['slave_cache_hostname']),
            'cachePort' => getenv(name: Common::$req->s['cDetails']['slave_cache_port']),
            'cacheUsername' => getenv(name: Common::$req->s['cDetails']['slave_cache_username']),
            'cachePassword' => getenv(name: Common::$req->s['cDetails']['slave_cache_password']),
            'cacheDatabase' => getenv(name: Common::$req->s['cDetails']['slave_cache_database']),
            'cacheTable' => getenv(name: Common::$req->s['cDetails']['slave_cache_table'])
        ];
    }

    /**
     * Returns Db Master Server Details
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return array
     */
    public static function getDbMasterDetails(): array
    {
        return [
            'dbType' => getenv(name: Common::$req->s['cDetails']['master_db_server_type']),
            'dbHostname' => getenv(name: Common::$req->s['cDetails']['master_db_hostname']),
            'dbPort' => getenv(name: Common::$req->s['cDetails']['master_db_port']),
            'dbUsername' => getenv(name: Common::$req->s['cDetails']['master_db_username']),
            'dbPassword' => getenv(name: Common::$req->s['cDetails']['master_db_password']),
            'dbDatabase' => getenv(name: Common::$req->s['cDetails']['master_db_database']),
        ];
    }

    /**
     * Returns Database Slave Server Details
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return array
     */
    public static function getDbSlaveDetails(): array
    {
        return [
            'dbType' => getenv(name: Common::$req->s['cDetails']['slave_db_server_type']),
            'dbHostname' => getenv(name: Common::$req->s['cDetails']['slave_db_hostname']),
            'dbPort' => getenv(name: Common::$req->s['cDetails']['slave_db_port']),
            'dbUsername' => getenv(name: Common::$req->s['cDetails']['slave_db_username']),
            'dbPassword' => getenv(name: Common::$req->s['cDetails']['slave_db_password']),
            'dbDatabase' => getenv(name: Common::$req->s['cDetails']['slave_db_database']),
        ];
    }
}
