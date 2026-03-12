<?php

/**
 * Cache
 * php version 8.3
 *
 * @category  Server
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server;

use Microservices\App\HttpStatus;
use Microservices\App\Server\CacheServer\CacheServerInterface;

/**
 * Cache Server
 * php version 8.3
 *
 * @category  Cache Server
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
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
	private $cacheServerDB = null;

	/**
	 * Cache collection
	 *
	 * @var null|string
	 */
	public $cacheServerTable = null;

	/**
	 * Constructor
	 *
	 * @param string      $cacheServerType     Cache Server Type
	 * @param string      $cacheServerHostname Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUsername Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDB       Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 *
	 * @return CacheServerInterface
	 */
	public function __construct(
        $cacheServerType,
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDB,
		$cacheServerTable
	) {
		$this->cacheServerType = $cacheServerType;
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDB = $cacheServerDB;
		$this->cacheServerTable = $cacheServerTable;

		return $this->connectCacheServer();
	}

	/**
	 * Init server connection based on $fetchFrom
	 *
	 * @return CacheServerInterface
	 */
	public static function connectCacheServer(): CacheServerInterface
	{
		if (
            !in_array(
                $this->cacheServerType, [
                    'Redis',
                    'Memcached',
                    'MongoDb'
                ]
            )
        ) {
			throw new \Exception(
				message: 'Invalid Cache type',
				code: HttpStatus::$InternalServerError
			);
		}

		$cacheServerNS = 'Microservices\\App\\Server\\CacheServer\\'
            . $this->cacheServerType . 'Cache';

		return new $cacheServerNS(
			cacheServerHostname: $this->cacheServerHostname,
			cacheServerPort: $this->cacheServerPort,
			cacheServerUsername: $this->cacheServerUsername,
			cacheServerPassword: $this->cacheServerPassword,
			cacheServerDB: $this->cacheServerDB,
			cacheServerTable: $this->cacheServerTable
		);
	}
}
