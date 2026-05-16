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
	public $exportDbServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string $dbServerType Database Type (eg. MySql)
	 */
	public function __construct($dbServerType)
	{
		$this->dbServerType = $dbServerType;
		$class = "Microservices\\App\\Export\\Container\\" . $this->dbServerType;
		$this->exportDbServerObj = new $class();
	}

	/**
	 * Initialize
	 *
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDatabase Database Server Database
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function init(
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDatabase
	): void {
		$this->exportDbServerObj->init(
			dbServerHostname: $dbServerHostname,
			dbServerPort: $dbServerPort,
			dbServerUsername: $dbServerUsername,
			dbServerPassword: $dbServerPassword,
			dbServerDatabase: $dbServerDatabase
		);
	}

	/**
	 * Returns Shell Command
	 *
	 * @param string $sql      SQL query
	 * @param array  $paramArr SQL query params
	 *
	 * @return string
	 */
	public function getShellCommand($sql, $paramArr = []): string
	{
		// Validation
		if (empty($sql)) {
			throw new \Exception(message: 'Empty SQL query');
		}

		return $this->exportDbServerObj->getShellCommand(
			sql: $sql,
			paramArr: $paramArr
		);
	}
}
