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

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseOpenCacheKey;
use Microservices\App\HttpRequest;
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
     * Rate Limiter
     *
     * @var null|HttpRequest
     */
    private $req = null;

    /**
     * Query Cache Connection Object
     *
     * @var null|Object
     */
    private $queryCacheConnection = null;

    /**
     * Constructor
     *
     * @param HttpRequest $req HTTP Request object
     */
    public function __construct(&$req)
    {
        $this->req = &$req;
    }


    /**
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     *
     * @return void
     */
    public function setQueryCacheConnection(): void
    {
        if ($this->queryCacheConnection !== null) {
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
        $this->queryCacheConnection = new $queryCacheNS(
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
    public function connectCache(
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
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     *
     * @return object
     * @throws \Exception
     */
    public function setCacheConnection($fetchFrom): object
    {
        if ($this->req->s['cDetails'] === null) {
            throw new \Exception(
                message: 'Yet to set connection params',
                code: HttpStatus::$InternalServerError
            );
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                return $this->connectCache(
                    cacheType: getenv(
                        name: $this->req->s['cDetails']['master_cache_server_type']
                    ),
                    cacheHostname: getenv(
                        name: $this->req->s['cDetails']['master_cache_hostname']
                    ),
                    cachePort: getenv(
                        name: $this->req->s['cDetails']['master_cache_port']
                    ),
                    cacheUsername: getenv(
                        name: $this->req->s['cDetails']['master_cache_username']
                    ),
                    cachePassword: getenv(
                        name: $this->req->s['cDetails']['master_cache_password']
                    ),
                    cacheDatabase: getenv(
                        name: $this->req->s['cDetails']['master_cache_database']
                    ),
                    cacheTable:  getenv(
                        name: $this->req->s['cDetails']['master_cache_table']
                    )
                );
            case 'Slave':
                return $this->connectCache(
                    cacheType: getenv(
                        name: $this->req->s['cDetails']['slave_cache_server_type']
                    ),
                    cacheHostname: getenv(
                        name: $this->req->s['cDetails']['slave_cache_hostname']
                    ),
                    cachePort: getenv(
                        name: $this->req->s['cDetails']['slave_cache_port']
                    ),
                    cacheUsername: getenv(
                        name: $this->req->s['cDetails']['slave_cache_username']
                    ),
                    cachePassword: getenv(
                        name: $this->req->s['cDetails']['slave_cache_password']
                    ),
                    cacheDatabase: getenv(
                        name: $this->req->s['cDetails']['slave_cache_database']
                    ),
                    cacheTable:  getenv(
                        name: $this->req->s['cDetails']['slave_cache_table']
                    )
                );
            default:
                throw new \Exception(
                    message: "Invalid fetchFrom value '{$fetchFrom}'",
                    code: HttpStatus::$InternalServerError
                );
        }
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
    public function connectDb(
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
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     *
     * @return object
     * @throws \Exception
     */
    public function setDbConnection($fetchFrom): object
    {
        if ($this->req->s['cDetails'] === null) {
            throw new \Exception(
                message: 'Yet to set connection params',
                code: HttpStatus::$InternalServerError
            );
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                return $this->connectDb(
                    dbType: getenv(
                        name: $this->req->s['cDetails']['master_db_server_type']
                    ),
                    dbHostname: getenv(
                        name: $this->req->s['cDetails']['master_db_hostname']
                    ),
                    dbPort: getenv(
                        name: $this->req->s['cDetails']['master_db_port']
                    ),
                    dbUsername: getenv(
                        name: $this->req->s['cDetails']['master_db_username']
                    ),
                    dbPassword: getenv(
                        name: $this->req->s['cDetails']['master_db_password']
                    ),
                    dbDatabase: getenv(
                        name: $this->req->s['cDetails']['master_db_database']
                    )
                );
            case 'Slave':
                return $this->connectDb(
                    dbType: getenv(
                        name: $this->req->s['cDetails']['slave_db_server_type']
                    ),
                    dbHostname: getenv(
                        name: $this->req->s['cDetails']['slave_db_hostname']
                    ),
                    dbPort: getenv(
                        name: $this->req->s['cDetails']['slave_db_port']
                    ),
                    dbUsername: getenv(
                        name: $this->req->s['cDetails']['slave_db_username']
                    ),
                    dbPassword: getenv(
                        name: $this->req->s['cDetails']['slave_db_password']
                    ),
                    dbDatabase: getenv(
                        name: $this->req->s['cDetails']['slave_db_database']
                    )
                );
            default:
                throw new \Exception(
                    message: "Invalid fetchFrom value '{$fetchFrom}'",
                    code: HttpStatus::$InternalServerError
                );
        }
    }

    /**
     * Set Cache prefix key
     *
     * @return void
     */
    public function setDatabaseCacheKey(): void
    {
        if ($this->req->open) {
            DatabaseOpenCacheKey::init(cID: $this->req->s['cDetails']['id']);
        } else {
            DatabaseCacheKey::init(
                cID: $this->req->s['cDetails']['id'],
                gID: $this->req->s['gDetails']['id'],
                uID: $this->req->s['uDetails']['id']
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
    public function getQueryCache($cacheKey): mixed
    {
        $this->setQueryCacheConnection();

        if ($this->queryCacheConnection->cacheExists(key: $cacheKey)) {
            return $json = $this->queryCacheConnection->getCache(key: $cacheKey);
        } else {
            return $json = null;
        }
    }

    /**
     * Set Query cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     * @param string $json     JSON
     *
     * @return void
     */
    public function setQueryCache($cacheKey, &$json): void
    {
        $this->setQueryCacheConnection();

        $this->queryCacheConnection->setCache(key: $cacheKey, value: $json);
    }

    /**
     * Delete Query Cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return void
     */
    public function delQueryCache($cacheKey): void
    {
        $this->setQueryCacheConnection();

        $this->queryCacheConnection->deleteCache(key: $cacheKey);
    }
}
