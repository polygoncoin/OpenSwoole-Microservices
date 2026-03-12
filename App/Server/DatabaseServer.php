<?php

/**
 * Database
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
use Microservices\App\Server\DatabaseServer\DatabaseServerInterface;

/**
 * Database Server
 * php version 8.3
 *
 * @category  Database Server
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
class DbServer
{
	/**
	 * Database Server Type
	 *
	 * @var null|string
	 */
	private $dbServerType = null;

	/**
	 * Database Server Hostname
	 *
	 * @var null|string
	 */
	private $dbServerHostname = null;

	/**
	 * Database Server Port
	 *
	 * @var null|int
	 */
	private $dbServerPort = null;

	/**
	 * Database Server Username
	 *
	 * @var null|string
	 */
	private $dbServerUsername = null;

	/**
	 * Database Server Password
	 *
	 * @var null|string
	 */
	private $dbServerPassword = null;

	/**
	 * Database Server DB
	 *
	 * @var null|string
	 */
	private $dbServerDB = null;

	/**
	 * Constructor
	 *
	 * @param string      $dbServerType     Database Server Type
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDB       Database Server Database
	 *
	 * @return DatabaseServerInterface
	 */
	public function __construct(
        $dbServerType,
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDB
	) {
		$this->dbServerType = $dbServerType;
		$this->dbServerHostname = $dbServerHostname;
		$this->dbServerPort = $dbServerPort;
		$this->dbServerUsername = $dbServerUsername;
		$this->dbServerPassword = $dbServerPassword;
		$this->dbServerDB = $dbServerDB;

		return $this->connectDatabaseServer();
	}

	/**
	 * Init server connection based on $fetchFrom
	 *
	 * @return DatabaseServerInterface
	 */
	public static function connectDatabaseServer(): DatabaseServerInterface
	{
		if (!in_array($this->dbServerType, ['MySql', 'PostgreSql'])) {
			throw new \Exception(
				message: "Invalid Database type '{$this->dbServerType}'",
				code: HttpStatus::$InternalServerError
			);
		}

		$dbServerNS = 'Microservices\\App\\Server\\DatabaseServer\\'
            . $this->dbServerType . 'Database';

		return new $dbServerNS(
			dbServerHostname: $this->dbServerHostname,
			dbServerPort: $this->dbServerPort,
			dbServerUsername: $this->dbServerUsername,
			dbServerPassword: $this->dbServerPassword,
			dbServerDB: $this->dbServerDB
		);
	}
}
