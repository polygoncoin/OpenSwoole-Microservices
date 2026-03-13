<?php

/**
 * Export CSV
 * php version 8.3
 *
 * @category  Export
 * @package   Openswoole_Microservices
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
 * @package   Openswoole_Microservices
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
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDB       Database Server Database
	 *
	 * @return void
	 */
	public function init($dbServerHostname, $dbServerPort, $dbServerUsername, $dbServerPassword, $dbServerDB): void;

	/**
	 * Returns Shell Command
	 *
	 * @param string $sql    query
	 * @param array  $params query params
	 *
	 * @return string
	 */
	public function getShellCommand($sql, $params = null): string;
}
