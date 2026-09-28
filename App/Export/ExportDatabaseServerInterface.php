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

/**
 * Export CSV Interface
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
interface ExportDatabaseServerInterface
{
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
	 */
	public function init(
		$dbServerHost,
		$dbServerPort,
		$dbServerUser,
		$dbServerPassword,
		$dbServerDb
	): void;

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
		$paramArray = null
	): string;
}
