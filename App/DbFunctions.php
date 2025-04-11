<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\DatabaseCacheKey;
use Microservices\App\Env;
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
    public function setCache(
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
                $this->sqlCache = $this->setCache(
                    getenv($this->session['clientDetails']['master_cache_server_type']),
                    getenv($this->session['clientDetails']['master_cache_hostname']),
                    getenv($this->session['clientDetails']['master_cache_port']),
                    getenv($this->session['clientDetails']['master_cache_username']),
                    getenv($this->session['clientDetails']['master_cache_password']),
                    getenv($this->session['clientDetails']['master_cache_database'])
                );
                break;
            case 'Slave':
                $this->sqlCache = $this->setCache(
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
    public function setDb(
        $dbType,
        $dbHostname,
        $dbPort,
        $dbUsername,
        $dbPassword,
        $dbDatabase
    )
    {
        $dbNS = 'Microservices\\App\\Servers\\Database\\'.$dbType;
        $this->db = new $dbNS(
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
                $this->setDb(
                    getenv($this->session['clientDetails']['master_db_server_type']),
                    getenv($this->session['clientDetails']['master_db_hostname']),
                    getenv($this->session['clientDetails']['master_db_port']),
                    getenv($this->session['clientDetails']['master_db_username']),
                    getenv($this->session['clientDetails']['master_db_password']),
                    getenv($this->session['clientDetails']['master_db_database'])
                );
                break;
            case 'Slave':
                $this->setDb(
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
    public function getDqlCache(&$cacheKey)
    {
        if (is_null($this->sqlCache)) {
            $this->setCacheConnection('Slave');
        }
        if ($this->sqlCache->cacheExists($cacheKey)) {
            return $json = $this->sqlCache->getCache($cacheKey);
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
    public function setDmlCache(&$cacheKey, &$json)
    {
        if (is_null($this->sqlCache)) {
            $this->setCacheConnection('Master');
        }
        $this->sqlCache->setCache($cacheKey, $json);
    }

    /**
     * Delete DQL Cache
     *
     * @param string $cacheKey Cache Key from Queries configuration
     * @return void
     */
    public function delDmlCache(&$cacheKey)
    {
        if (is_null($this->sqlCache)) {
            $this->setCacheConnection('Master');
        }
        $this->sqlCache->deleteCache($cacheKey);
    }
}
