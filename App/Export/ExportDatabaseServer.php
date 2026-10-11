<?php

/**
 * Export CSV
 * php version 8.3
 *
 * @category  Export
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Export;

use Microservices\App\Export\ExportDatabaseServerInterface;

/**
 * Export CSV
 * php version 8.3
 *
 * @category  Export
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class ExportDatabaseServer
{
	/**
	 * Allow creation of temporary file required for streaming large data
	 * 
	 * @var bool
	 */
	public $useTmpFile = false;

	/**
	 * Database Engine
	 * 
	 * @var null|string
	 */
	public $dbServerType = null;

	/**
	 * Database Class Object as per dbServerType
	 * 
	 * @var null|ExportDatabaseServerInterface
	 */
	public $exportDbObject = null;

	/**
	 * Constructor
	 * 
	 * @param string $dbServerType Database Type (eg. MySql)
	 */
	public function __construct(
		$dbServerType
	) {
		$this->dbServerType = $dbServerType;
		$class = "Microservices\\App\\Export\\Container\\" . $this->dbServerType;
		$this->exportDbObject = new $class();
	}

	/**
	 * Initialize
	 * 
	 * @param string      $dbServerHost Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUser Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDb Database Server Database
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function init(
		$dbServerHost,
		$dbServerPort,
		$dbServerUser,
		$dbServerPassword,
		$dbServerDb
	): void {
		$this->exportDbObject->init(
			dbServerHost: $dbServerHost,
			dbServerPort: $dbServerPort,
			dbServerUser: $dbServerUser,
			dbServerPassword: $dbServerPassword,
			dbServerDb: $dbServerDb
		);
	}

	/**
	 * Returns Shell Command
	 * 
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 * 
	 * @return string
	 */
	public function getShellCommand(
		$sql,
		$paramArray = []
	): string {
		// Validation
		if (empty($sql)) {
			throw new \Exception(
				message: 'Empty Sql query'
			);
		}

		return $this->exportDbObject->getShellCommand(
			sql: $sql,
			paramArray: $paramArray
		);
	}
}
