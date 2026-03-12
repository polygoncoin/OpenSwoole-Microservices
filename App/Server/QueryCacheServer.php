<?php

/**
 * Query Cache
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
use Microservices\App\Server\QueryCacheServer\QueryCacheServerInterface;

/**
 * Query Cache Server
 * php version 8.3
 *
 * @category  Query Cache Server
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
class QueryCacheServer
{
	/**
	 * Query Cache Server Type
	 *
	 * @var null|string
	 */
	private $queryCacheServerType = null;

	/**
	 * Query Cache Server Hostname
	 *
	 * @var null|string
	 */
	private $queryCacheServerHostname = null;

	/**
	 * Query Cache Server Port
	 *
	 * @var null|int
	 */
	private $queryCacheServerPort = null;

	/**
	 * Query Cache Server Username
	 *
	 * @var null|string
	 */
	private $queryCacheServerUsername = null;

	/**
	 * Query Cache Server Password
	 *
	 * @var null|string
	 */
	private $queryCacheServerPassword = null;

	/**
	 * Query Cache Server DB
	 *
	 * @var null|string
	 */
	private $queryCacheServerDB = null;

	/**
	 * Cache collection
	 *
	 * @var null|string
	 */
	public $queryCacheServerTable = null;

	/**
	 * Constructor
	 *
	 * @param string      $queryCacheServerType     Query Cache Server Type
	 * @param string      $queryCacheServerHostname Query Cache Server Hostname
	 * @param int         $queryCacheServerPort     Query Cache Server Port
	 * @param string      $queryCacheServerUsername Query Cache Server Username
	 * @param string      $queryCacheServerPassword Query Cache Server Password
	 * @param null|string $queryCacheServerDB       Query Cache Server Database
	 * @param null|string $queryCacheServerTable    Query Cache Server Table
	 *
	 * @return QueryCacheServerInterface
	 */
	public function __construct(
        $queryCacheServerType,
		$queryCacheServerHostname,
		$queryCacheServerPort,
		$queryCacheServerUsername,
		$queryCacheServerPassword,
		$queryCacheServerDB,
		$queryCacheServerTable
	) {
		$this->queryCacheServerType = $queryCacheServerType;
		$this->queryCacheServerHostname = $queryCacheServerHostname;
		$this->queryCacheServerPort = $queryCacheServerPort;
		$this->queryCacheServerUsername = $queryCacheServerUsername;
		$this->queryCacheServerPassword = $queryCacheServerPassword;
		$this->queryCacheServerDB = $queryCacheServerDB;
		$this->queryCacheServerTable = $queryCacheServerTable;

		return $this->connectQueryCacheServer();
	}

	/**
	 * Connect Query Cache Server
	 *
	 * @return QueryCacheServerInterface
	 */
	public static function connectQueryCacheServer(): QueryCacheServerInterface
	{
		if (
            !in_array(
                $this->queryCacheServerType, [
                    'Redis',
                    'Memcached',
                    'MongoDb',
                    'MySql',
                    'PostgreSql'
                ]
            )
        ) {
			throw new \Exception(
				message: 'Invalid query cache type',
				code: HttpStatus::$InternalServerError
			);
		}

		$queryCacheServerNS = 'Microservices\\App\\Server\\QueryCacheServer\\'
            . $this->queryCacheServerType . 'QueryCache';

		return new $queryCacheServerNS(
			queryCacheServerHostname: $this->queryCacheServerHostname,
			queryCacheServerPort: $this->queryCacheServerPort,
			queryCacheServerUsername: $this->queryCacheServerUsername,
			queryCacheServerPassword: $this->queryCacheServerPassword,
			queryCacheServerDB: $this->queryCacheServerDB,
			queryCacheServerTable: $this->queryCacheServerTable
		);
	}
}
