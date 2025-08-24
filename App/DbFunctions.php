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
     * Set Cache
     *
     * @param string $cacheType     Cache type
     * @param string $cacheHostname Hostname
     * @param int    $cachePort     Port
     * @param string $cacheUsername Username
     * @param string $cachePassword Password
     * @param string $cacheDatabase Database
     *
     * @return object
     */
    public function connectCache(
        $cacheType,
        $cacheHostname,
        $cachePort,
        $cacheUsername,
        $cachePassword,
        $cacheDatabase
    ): object {
        $cacheNS = 'Microservices\\App\\Servers\\Cache\\'.$cacheType;
        return new $cacheNS(
            $cacheHostname,
            $cachePort,
            $cacheUsername,
            $cachePassword,
            $cacheDatabase
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
        if ($this->session['clientDetails'] === null) {
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
                    name: $this->session['clientDetails']['master_cache_server_type']
                ),
                cacheHostname: getenv(
                    name: $this->session['clientDetails']['master_cache_hostname']
                ),
                cachePort: getenv(
                    name: $this->session['clientDetails']['master_cache_port']
                ),
                cacheUsername: getenv(
                    name: $this->session['clientDetails']['master_cache_username']
                ),
                cachePassword: getenv(
                    name: $this->session['clientDetails']['master_cache_password']
                ),
                cacheDatabase: getenv(
                    name: $this->session['clientDetails']['master_cache_database']
                )
            );
        case 'Slave':
            return $this->connectCache(
                cacheType: getenv(
                    name: $this->session['clientDetails']['slave_cache_server_type']
                ),
                cacheHostname: getenv(
                    name: $this->session['clientDetails']['slave_cache_hostname']
                ),
                cachePort: getenv(
                    name: $this->session['clientDetails']['slave_cache_port']
                ),
                cacheUsername: getenv(
                    name: $this->session['clientDetails']['slave_cache_username']
                ),
                cachePassword: getenv(
                    name: $this->session['clientDetails']['slave_cache_password']
                ),
                cacheDatabase: getenv(
                    name: $this->session['clientDetails']['slave_cache_database']
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
        $dbNS = 'Microservices\\App\\Servers\\Database\\'.$dbType;
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
        if ($this->session['clientDetails'] === null) {
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
                    name: $this->session['clientDetails']['master_db_server_type']
                ),
                dbHostname: getenv(
                    name: $this->session['clientDetails']['master_db_hostname']
                ),
                dbPort: getenv(
                    name: $this->session['clientDetails']['master_db_port']
                ),
                dbUsername: getenv(
                    name: $this->session['clientDetails']['master_db_username']
                ),
                dbPassword: getenv(
                    name: $this->session['clientDetails']['master_db_password']
                ),
                dbDatabase: getenv(
                    name: $this->session['clientDetails']['master_db_database']
                )
            );
        case 'Slave':
            return $this->connectDb(
                dbType: getenv(
                    name: $this->session['clientDetails']['slave_db_server_type']
                ),
                dbHostname: getenv(
                    name: $this->session['clientDetails']['slave_db_hostname']
                ),
                dbPort: getenv(
                    name: $this->session['clientDetails']['slave_db_port']
                ),
                dbUsername: getenv(
                    name: $this->session['clientDetails']['slave_db_username']
                ),
                dbPassword: getenv(
                    name: $this->session['clientDetails']['slave_db_password']
                ),
                dbDatabase: getenv(
                    name: $this->session['clientDetails']['slave_db_database']
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
        if ($this->open) {
            DatabaseOpenCacheKey::init(clientId: $this->clientId);
        } else {
            DatabaseCacheKey::init(
                clientId: $this->clientId,
                groupId: $this->groupId,
                userId: $this->userId
            );
        }
    }

    /**
     * Set Cache prefix key
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return mixed
     */
    public function getDqlCache($cacheKey): mixed
    {
        if ($this->sqlCache === null) {
            $this->sqlCache = $this->setCacheConnection(fetchFrom: 'Slave');
        }

        if ($this->sqlCache->cacheExists(key: $cacheKey)) {
            return $json = $this->sqlCache->getCache(key: $cacheKey);
        } else {
            return $json = null;
        }
    }

    /**
     * Set DQL Cache as JSON
     *
     * @param string $cacheKey Cache Key from Queries configuration
     * @param string $json     JSON
     *
     * @return void
     */
    public function setDmlCache($cacheKey, &$json): void
    {
        if ($this->sqlCache === null) {
            $this->sqlCache = $this->setCacheConnection(fetchFrom: 'Master');
        }

        $this->sqlCache->setCache(key: $cacheKey, value: $json);
    }

    /**
     * Delete DQL Cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     *
     * @return void
     */
    public function delDmlCache($cacheKey): void
    {
        if ($this->sqlCache === null) {
            $this->sqlCache = $this->setCacheConnection(fetchFrom: 'Master');
        }

        $this->sqlCache->deleteCache(key: $cacheKey);
    }
}
