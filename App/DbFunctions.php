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

use Microservices\App\HttpRequest;
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
    private static $sqlResultsCacheServer = null;

    /** Database Connection */
    /**
     * Global
     *
     * @var null|Object
     */
    public static $gDbServer = null;

    /**
     * Client Master
     *
     * @var Object[]
     */
    public static $masterDb = [];

    /**
     * Client Slave
     *
     * @var Object[]
     */
    public static $slaveDb = [];

    /** Cache Connection */
    /**
     * Global
     *
     * @var null|Object
     */
    public static $gCacheServer = null;

    /**
     * Client Master
     *
     * @var Object[]
     */
    public static $masterCache = [];

    /**
     * Client Slave
     *
     * @var Object[]
     */
    public static $slaveCache = [];

    /**
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     *
     * @return void
     */
    public static function connectQueryCache(): void
    {
        if (self::$sqlResultsCacheServer !== null) {
            return;
        }

        $cacheServerType = getenv(name: 'sqlResultsCacheServerType');
        if (!in_array($cacheServerType, ['Redis', 'Memcached', 'MongoDb', 'MySql', 'PostgreSql'])) {
            throw new \Exception(
                message: 'Invalid query cache type',
                code: HttpStatus::$InternalServerError
            );
        }

        $sqlResultsCacheServerNS = 'Microservices\\App\\Servers\\QueryCache\\' . $cacheServerType . 'QueryCache';
        self::$sqlResultsCacheServer = new $sqlResultsCacheServerNS(
            getenv(name: 'sqlResultsCacheServerHostname'),
            getenv(name: 'sqlResultsCacheServerPort'),
            getenv(name: 'sqlResultsCacheServerUsername'),
            getenv(name: 'sqlResultsCacheServerPassword'),
            getenv(name: 'sqlResultsCacheServerDatabase'),
            getenv(name: 'sqlResultsCacheServerTable')
        );
    }

    /**
     * Set Cache
     *
     * @param string $cacheServerType Cache type
     * @param string $cacheHostname   Hostname
     * @param int    $cachePort       Port
     * @param string $cacheUsername   Username
     * @param string $cachePassword   Password
     * @param string $cacheDatabase   Database
     * @param string $cacheTable      Table
     *
     * @return object
     */
    public static function connectCache(
        $cacheServerType,
        $cacheHostname,
        $cachePort,
        $cacheUsername,
        $cachePassword,
        $cacheDatabase,
        $cacheTable
    ): object {
        if (!in_array($cacheServerType, ['Redis', 'Memcached', 'MongoDb'])) {
            throw new \Exception(
                message: 'Invalid Cache type',
                code: HttpStatus::$InternalServerError
            );
        }
        $cacheNS = 'Microservices\\App\\Servers\\Cache\\' . $cacheServerType . 'Cache';
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
        if (self::$gCacheServer !== null) {
            return;
        }
        self::$gCacheServer = self::connectCache(
            cacheServerType: getenv(name: 'gCacheServerType'),
            cacheHostname: getenv(name: 'gCacheServerHostname'),
            cachePort: getenv(name: 'gCacheServerPort'),
            cacheUsername: getenv(name: 'gCacheServerUsername'),
            cachePassword: getenv(name: 'gCacheServerPassword'),
            cacheDatabase: getenv(name: 'gCacheServerDatabase'),
            cacheTable: getenv(name: 'gCacheServerTable')
        );
    }

    /**
     * Init server connection based on $fetchFrom
     *
     * @param HttpRequest $req
     * @param string      $fetchFrom Master/Slave
     *
     * @return void
     * @throws \Exception
     */
    public static function setCacheConnection($req, $fetchFrom): void
    {
        if ($req->s['cDetails'] === null) {
            throw new \Exception(
                message: 'Yet to set connection params',
                code: HttpStatus::$InternalServerError
            );
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                if (
                    isset(self::$masterCache[$req->s['cDetails']['id']])
                    && self::$masterCache[$req->s['cDetails']['id']] !== null
                ) {
                    return;
                }

                $masterCacheDetails = self::getCacheMasterDetails($req->s['cDetails']);
                self::$masterCache[$req->s['cDetails']['id']] = self::connectCache(
                    cacheServerType: $masterCacheDetails['cacheServerType'],
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

                $slaveCacheDetails = self::getCacheSlaveDetails($req->s['cDetails']);
                self::$slaveCache[$req->s['cDetails']['id']] = self::connectCache(
                    cacheServerType: $slaveCacheDetails['cacheServerType'],
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
     * @param string $dbServerType Cache type
     * @param string $dbHostname   Hostname
     * @param int    $dbPort       Port
     * @param string $dbUsername   Username
     * @param string $dbPassword   Password
     * @param string $dbDatabase   Database
     *
     * @return object
     */
    public static function connectDb(
        $dbServerType,
        $dbHostname,
        $dbPort,
        $dbUsername,
        $dbPassword,
        $dbDatabase
    ): object {
        if (!in_array($dbServerType, ['MySql', 'PostgreSql'])) {
            throw new \Exception(
                message: "Invalid Database type '{$dbServerType}'",
                code: HttpStatus::$InternalServerError
            );
        }
        $dbNS = 'Microservices\\App\\Servers\\Database\\' . $dbServerType . 'Database';
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
        if (self::$gDbServer !== null) {
            return;
        }
        self::$gDbServer = self::connectDb(
            dbServerType: getenv(name: 'gDbServerType'),
            dbHostname: getenv(name: 'gDbServerHostname'),
            dbPort: getenv(name: 'gDbServerPort'),
            dbUsername: getenv(name: 'gDbServerUsername'),
            dbPassword: getenv(name: 'gDbServerPassword'),
            dbDatabase: getenv(name: 'gDbServerDatabase')
        );
    }

    /**
     * Init server connection based on $fetchFrom
     *
     * @param HttpRequest $req
     * @param string      $fetchFrom Master/Slave
     *
     * @return void
     * @throws \Exception
     */
    public static function setDbConnection($req, $fetchFrom): void
    {
        if ($req->s['cDetails'] === null) {
            throw new \Exception(
                message: 'Yet to set connection params',
                code: HttpStatus::$InternalServerError
            );
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                if (
                    isset(self::$masterDb[$req->s['cDetails']['id']])
                    && self::$masterDb[$req->s['cDetails']['id']] !== null
                ) {
                    return;
                }

                $masterDbDetails = self::getDbMasterDetails($req->s['cDetails']);
                self::$masterDb[$req->s['cDetails']['id']] = self::connectDb(
                    dbServerType: $masterDbDetails['dbServerType'],
                    dbHostname: $masterDbDetails['dbHostname'],
                    dbPort: $masterDbDetails['dbPort'],
                    dbUsername: $masterDbDetails['dbUsername'],
                    dbPassword: $masterDbDetails['dbPassword'],
                    dbDatabase: $masterDbDetails['dbDatabase']
                );
                break;
            case 'Slave':
                if (
                    isset(self::$slaveDb[$req->s['cDetails']['id']])
                    && self::$slaveDb[$req->s['cDetails']['id']] !== null
                ) {
                    return;
                }

                $slaveDbDetails = self::getDbSlaveDetails($req->s['cDetails']);
                self::$slaveDb[$req->s['cDetails']['id']] = self::connectDb(
                    dbServerType: $slaveDbDetails['dbServerType'],
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
     * @param HttpRequest $req
     *
     * @return void
     */
    public static function setDatabaseCacheKey($req): void
    {
        if ($req->open) {
            DatabaseOpenCacheKey::init(cID: $req->s['cDetails']['id']);
        } else {
            DatabaseCacheKey::init(
                cID: $req->s['cDetails']['id'],
                gID: $req->s['gDetails']['id'],
                uID: $req->s['uDetails']['id']
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
        if (self::$sqlResultsCacheServer->cacheExists(key: $cacheKey)) {
            $json = self::$sqlResultsCacheServer->getCache(key: $cacheKey);
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

        self::$sqlResultsCacheServer->setCache(key: $cacheKey, value: $json);
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

        self::$sqlResultsCacheServer->deleteCache(key: $cacheKey);
    }

    /**
     * Returns Cache Master Server Details
     *
     * @param array $cDetails Client details
     *
     * @return array
     */
    public static function getCacheMasterDetails($cDetails): array
    {
        return [
            'cacheServerType' => getenv(name: $cDetails['master_cache_server_type']),
            'cacheHostname' => getenv(name: $cDetails['master_cache_hostname']),
            'cachePort' => getenv(name: $cDetails['master_cache_port']),
            'cacheUsername' => getenv(name: $cDetails['master_cache_username']),
            'cachePassword' => getenv(name: $cDetails['master_cache_password']),
            'cacheDatabase' => getenv(name: $cDetails['master_cache_database']),
            'cacheTable' => getenv(name: $cDetails['master_cache_table'])
        ];
    }

    /**
     * Returns Cache Slave Server Details
     *
     * @param array $cDetails Client details
     *
     * @return array
     */
    public static function getCacheSlaveDetails($cDetails): array
    {
        return [
            'cacheServerType' => getenv(name: $cDetails['slave_cache_server_type']),
            'cacheHostname' => getenv(name: $cDetails['slave_cache_hostname']),
            'cachePort' => getenv(name: $cDetails['slave_cache_port']),
            'cacheUsername' => getenv(name: $cDetails['slave_cache_username']),
            'cachePassword' => getenv(name: $cDetails['slave_cache_password']),
            'cacheDatabase' => getenv(name: $cDetails['slave_cache_database']),
            'cacheTable' => getenv(name: $cDetails['slave_cache_table'])
        ];
    }

    /**
     * Returns Db Master Server Details
     *
     * @param array $cDetails Client details
     *
     * @return array
     */
    public static function getDbMasterDetails($cDetails): array
    {
        return [
            'dbServerType' => getenv(name: $cDetails['master_db_server_type']),
            'dbHostname' => getenv(name: $cDetails['master_db_hostname']),
            'dbPort' => getenv(name: $cDetails['master_db_port']),
            'dbUsername' => getenv(name: $cDetails['master_db_username']),
            'dbPassword' => getenv(name: $cDetails['master_db_password']),
            'dbDatabase' => getenv(name: $cDetails['master_db_database']),
        ];
    }

    /**
     * Returns Database Slave Server Details
     *
     * @param array $cDetails Client details
     *
     * @return array
     */
    public static function getDbSlaveDetails($cDetails): array
    {
        return [
            'dbServerType' => getenv(name: $cDetails['slave_db_server_type']),
            'dbHostname' => getenv(name: $cDetails['slave_db_hostname']),
            'dbPort' => getenv(name: $cDetails['slave_db_port']),
            'dbUsername' => getenv(name: $cDetails['slave_db_username']),
            'dbPassword' => getenv(name: $cDetails['slave_db_password']),
            'dbDatabase' => getenv(name: $cDetails['slave_db_database']),
        ];
    }
}
