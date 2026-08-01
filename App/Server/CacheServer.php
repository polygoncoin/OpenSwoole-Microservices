<?php

/**
 * Cache
 * php version 8.3
 * 
 * @category  Server
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server;

use Microservices\App\Constant;
use Microservices\App\HttpStatus;
use Microservices\App\Server\CacheServer\CacheServerInterface;

/**
 * Cache Server
 * php version 8.3
 * 
 * @category  Cache Server
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class CacheServer
{
	/**
	 * Cache Server Type
	 * 
	 * @var null|string
	 */
	private $cacheServerType = null;

	/**
	 * Cache Server Hostname
	 * 
	 * @var null|string
	 */
	private $cacheServerHostname = null;

	/**
	 * Cache Server Port
	 * 
	 * @var null|int
	 */
	private $cacheServerPort = null;

	/**
	 * Cache Server Username
	 * 
	 * @var null|string
	 */
	private $cacheServerUsername = null;

	/**
	 * Cache Server Password
	 * 
	 * @var null|string
	 */
	private $cacheServerPassword = null;

	/**
	 * Cache Server DB
	 * 
	 * @var null|string
	 */
	private $cacheServerDatabase = null;

	/**
	 * Cache collection
	 * 
	 * @var null|string
	 */
	public $cacheServerTable = null;

	/**
	 * Cache Server Object
	 * 
	 * @var null|CacheServerInterface
	 */
	private $cacheServerObject = null;

	/**
	 * Constructor
	 * 
	 * @param string      $cacheServerType     Cache Server Type
	 * @param string      $cacheServerHostname Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUsername Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDatabase Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 */
	public function __construct(
        $cacheServerType,
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDatabase,
		$cacheServerTable
	) {
		$this->cacheServerType = $cacheServerType;
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDatabase = $cacheServerDatabase;
		$this->cacheServerTable = $cacheServerTable;
	}

	/**
	 * Connect Cache
	 * 
	 * @return void
	 */
	public function connectCache(): void
	{
		if ($this->cacheServerObject !== Constant::$NULL) {
			return;
		}

		if (
            !in_array(
                needle: $this->cacheServerType,
				haystack: [
                    'Redis',
                    'Memcached',
                    'MongoDb'
                ],
				strict: Constant::$TRUE
            )
        ) {
			throw new \Exception(
				message: 'Invalid Cache type',
				code: HttpStatus::$InternalServerError
			);
		}

		$cacheServerNS = 'Microservices\\App\\Server\\CacheServer\\'
            . $this->cacheServerType . 'Cache';

		$this->cacheServerObject = new $cacheServerNS(
			cacheServerHostname: $this->cacheServerHostname,
			cacheServerPort: $this->cacheServerPort,
			cacheServerUsername: $this->cacheServerUsername,
			cacheServerPassword: $this->cacheServerPassword,
			cacheServerDatabase: $this->cacheServerDatabase,
			cacheServerTable: $this->cacheServerTable
		);
	}

	/**
	 * Cache key exist
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	public function cacheExist(
		$cacheKey
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return false;
		}

		return $this->cacheServerObject->cacheExist(
			cacheKey: $cacheKey
		);
	}

	/**
	 * Get cache key
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	public function cacheGet(
		$cacheKey
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return false;
		}

		return $this->cacheServerObject->cacheGet(
			cacheKey: $cacheKey
		);
	}

	/**
	 * Set cache key
	 * 
	 * @param string $cacheKey    Cache key
	 * @param mixed  $cacheValue  Cache value
	 * @param int    $cacheExpire Seconds to expire. Default 0 - doesn't expire
	 * 
	 * @return mixed
	 */
	public function cacheSet(
		$cacheKey,
		$cacheValue,
		$cacheExpire = null
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return false;
		}

		return $this->cacheServerObject->cacheSet(
			cacheKey: $cacheKey,
			cacheValue: $cacheValue,
			cacheExpire: $cacheExpire
		);
	}

	/**
	 * Increment cache key with offset
	 * 
	 * @param string $cacheKey    Cache key
	 * @param int    $cacheOffset Offset
	 * 
	 * @return mixed
	 */
	public function cacheIncrement(
		$cacheKey,
		$cacheOffset = 1
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return false;
		}

		return $this->cacheServerObject->cacheIncrement(
			cacheKey: $cacheKey,
			cacheOffset: $cacheOffset
		);
	}

	/**
	 * Delete cache key
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	public function cacheDelete(
		$cacheKey
	): mixed {
		$this->connectCache();

		if (empty($cacheKey)) {
			return false;
		}

		return $this->cacheServerObject->cacheDelete(
			cacheKey: $cacheKey
		);
	}
}
