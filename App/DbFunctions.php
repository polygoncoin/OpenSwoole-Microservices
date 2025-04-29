<?php
namespace Microservices\App;

use Microservices\App\CacheKey;
use Microservices\App\DatabaseCacheKey;
use Microservices\App\HttpStatus;

/*
 * DB related functions
 *
 * @category   DB Functions
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class DbFunctions
{
    /**
     * Set Cache
     *
     * @return void
     */
    public function connectCache(
        $cacheType,
        $cacheHostname,
        $cachePort,
        $cacheUsername,
        $cachePassword,
        $cacheDatabase
    )
    {
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
     * @return void
     * @throws \Exception
     */
    public function setCacheConnection($fetchFrom)
    {
        if (is_null($this->session['clientDetails'])) {
            throw new \Exception('Yet to set connection params', HttpStatus::$InternalServerError);
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                return $this->connectCache(
                    getenv($this->session['clientDetails']['master_cache_server_type']),
                    getenv($this->session['clientDetails']['master_cache_hostname']),
                    getenv($this->session['clientDetails']['master_cache_port']),
                    getenv($this->session['clientDetails']['master_cache_username']),
                    getenv($this->session['clientDetails']['master_cache_password']),
                    getenv($this->session['clientDetails']['master_cache_database'])
                );
                break;
            case 'Slave':
                return $this->connectCache(
                    getenv($this->session['clientDetails']['slave_cache_server_type']),
                    getenv($this->session['clientDetails']['slave_cache_hostname']),
                    getenv($this->session['clientDetails']['slave_cache_port']),
                    getenv($this->session['clientDetails']['slave_cache_username']),
                    getenv($this->session['clientDetails']['slave_cache_password']),
                    getenv($this->session['clientDetails']['slave_cache_database'])
                );
                break;
            default:
                throw new \Exception("Invalid fetchFrom value '{$fetchFrom}'", HttpStatus::$InternalServerError);
        }
    }

    /**
     * Set DB
     *
     * @return void
     */
    public function connectDb(
        $dbType,
        $dbHostname,
        $dbPort,
        $dbUsername,
        $dbPassword,
        $dbDatabase
    )
    {
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
     * @return void
     * @throws \Exception
     */
    public function setDbConnection($fetchFrom)
    {
        if (is_null($this->session['clientDetails'])) {
            throw new \Exception('Yet to set connection params', HttpStatus::$InternalServerError);
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                return $this->connectDb(
                    getenv($this->session['clientDetails']['master_db_server_type']),
                    getenv($this->session['clientDetails']['master_db_hostname']),
                    getenv($this->session['clientDetails']['master_db_port']),
                    getenv($this->session['clientDetails']['master_db_username']),
                    getenv($this->session['clientDetails']['master_db_password']),
                    getenv($this->session['clientDetails']['master_db_database'])
                );
                break;
            case 'Slave':
                return $this->connectDb(
                    getenv($this->session['clientDetails']['slave_db_server_type']),
                    getenv($this->session['clientDetails']['slave_db_hostname']),
                    getenv($this->session['clientDetails']['slave_db_port']),
                    getenv($this->session['clientDetails']['slave_db_username']),
                    getenv($this->session['clientDetails']['slave_db_password']),
                    getenv($this->session['clientDetails']['slave_db_database'])
                );
                break;
            default:
                throw new \Exception("Invalid fetchFrom value '{$fetchFrom}'", HttpStatus::$InternalServerError);
        }
    }

    /**
     * Set Cache prefix key
     *
     * @return void
     */
    public function setDatabaseCacheKey()
    {
        DatabaseCacheKey::init($this->clientId, $this->groupId, $this->userId);
    }

    /**
     * Set Cache prefix key
     *
     * @param string $cacheKey Cache Key from Queries configuration
     * @return null|string
     */
    public function getDqlCache($cacheKey)
    {
        if (is_null($this->sqlCache)) {
            $this->sqlCache = $this->setCacheConnection('Slave');
        }
        $key = ($this->open) ? "open:$cacheKey" : $cacheKey;
        if ($this->sqlCache->cacheExists($key)) {
            return $json = $this->sqlCache->getCache($key);
        } else {
            return $json = null;
        }
    }

    /**
     * Set DQL Cache as JSON
     *
     * @param string $cacheKey Cache Key from Queries configuration
     * @param string $json     JSON
     * @return void
     */
    public function setDmlCache($cacheKey, &$json)
    {
        if (is_null($this->sqlCache)) {
            $this->sqlCache = $this->setCacheConnection('Master');
        }
        $key = ($this->open) ? "open:$cacheKey" : $cacheKey;
        $this->sqlCache->setCache($key, $json);
    }

    /**
     * Delete DQL Cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     * @return void
     */
    public function delDmlCache($cacheKey)
    {
        if (is_null($this->sqlCache)) {
            $this->sqlCache = $this->setCacheConnection('Master');
        }
        $key = ($this->open) ? "open:$cacheKey" : $cacheKey;
        $this->sqlCache->deleteCache($key);
    }
}
