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

namespace Microservices\App\Export\Container;

use Microservices\App\Env;
use Microservices\App\Export\ExportDatabaseServerInterface;

/**
 * Export CSV MySql container.
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
class MySql implements ExportDatabaseServerInterface
{
	/**
	 * Database Server Hostname
	 *
	 * @var null|string
	 */
	private $dbServerHostname = null;

	/**
	 * Database Server Port
	 *
	 * @var null|string
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
	public $dbServerDB = null;

	/**
	 * Mysql Customer binary location (One can find this by "which mysql" command)
	 *
	 * @var null|string
	 */
	private $binaryLoc = null;

	/**
	 * Constructor
	 *
	 * @throws \Exception
	 */
	public function __construct()
	{
		$requiredExtension = 'mysqli';
		if (!extension_loaded(extension: $requiredExtension)) {
			if (!dl(extension_filename: $requiredExtension . '.so')) {
				throw new \Exception(
					message: "Required PHP extension '{$requiredExtension}' missing"
				);
			}
		}
		$this->binaryLoc = Env::$mySqlBinaryLocationOnWebServer;
		if (!file_exists(filename: $this->binaryLoc)) {
			throw new \Exception(message: 'Issue: missing MySql Customer locally');
		}
	}

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
	public function init(
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDB
	): void
	{
		$this->dbServerHostname = $dbServerHostname;
		$this->dbServerPort = $dbServerPort;
		$this->dbServerUsername = $dbServerUsername;
		$this->dbServerPassword = $dbServerPassword;
		$this->dbServerDB = $dbServerDB;
	}

	/**
	 * Validate
	 *
	 * @param string $sql    query
	 * @param array  $params query params
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validate($sql, $params): void
	{
		if (empty($sql)) {
			throw new \Exception(message: 'Empty Sql query');
		}

		if (count(value: $params) === 0) {
			return;
		}

		//Validate parameterized query.
		if (
			substr_count(
				haystack: $sql,
				needle: ':'
				!== count(value: $params)
			)
		) {
			throw new \Exception(
				message: 'Parameterized query has mismatch in number of params'
			);
		}

		$paramKeys = array_keys(array: $params);
		$paramPos = [];
		foreach ($paramKeys as $value) {
			if (substr_count(haystack: $sql, needle: $value) > 1) {
				throw new \Exception(
					message: 'Parameterized query has more than one '
						. "occurrence of param '{$value}'"
				);
			}
			$paramPos[$value] = strpos(haystack: $sql, needle: $value);
		}
		foreach ($paramPos as $key => $value) {
			if (
				substr(
					string: $sql,
					offset: $value,
					length: strlen(string: $key)
					!== $key
				)
			) {
				throw new \Exception(message: "Invalid param key '{$key}'");
			}
		}
	}

	/**
	 * Generate raw Sql query from parameterized query via PDO.
	 *
	 * @param string $sql    query
	 * @param array  $params query params
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function generateRawSqlQuery($sql, $params): string
	{
		if (empty($params) || count(value: $params) === 0) {
			return $sql;
		}

		$this->validate(sql: $sql, params: $params);

		//mysqli connection
		$mysqli = mysqli_connect(
			hostname: $this->dbServerHostname,
			username: $this->dbServerUsername,
			password: $this->dbServerPassword,
			db: $this->dbServerDB,
			port: $this->dbServerPort
		);
		if (!$mysqli) {
			throw new \Exception(
				message: 'Connection error: ' . mysqli_connect_error()
			);
		}

		//Generate bind params
		$bindParams = [];
		foreach ($params as $key => $values) {
			if (is_array(value: $values)) {
				$tmpParams = [];
				$count = 1;
				foreach ($values as $value) {
					if (is_array(value: $value)) {
						throw new \Exception(
							message: "Invalid params for key '{$key}'"
						);
					}
					$newKey = $key . $count;
					if (in_array(needle: $newKey, haystack: $tmpParams)) {
						throw new \Exception(
							message: "Invalid parameterized params '{$newKey}'"
						);
					}
					$tmpParams[$key . $count++] = $value;
				}
				$sql = str_replace(
					search: $key,
					replace: implode(
						separator: ', ',
						array: array_keys(array: $tmpParams)
					),
					subject: $sql
				);
				$bindParams = array_merge($bindParams, $tmpParams);
			} else {
				$bindParams[$key] = $values;
			}
		}

		//Replace parameterized values.
		foreach ($bindParams as $key => $value) {
			if (!ctype_digit(text: $value)) {
				$value = "'" . mysqli_real_escape_string(
					mysql: $mysqli,
					string: $value
				) . "'";
			}
			$sql = str_replace(search: $key, replace: $value, subject: $sql);
		}

		// Close mysqli connection.
		mysqli_close(mysql: $mysqli);

		return $sql;
	}

	/**
	 * Returns Shell Command
	 *
	 * @param string $sql    query
	 * @param array  $params query params
	 *
	 * @return string
	 */
	public function getShellCommand($sql, $params = null): string
	{
		$sql = $this->generateRawSqlQuery(sql: $sql, params: $params);

		// Shell command.
		$shellCommand = $this->binaryLoc . ' '
			. '--host=' . escapeshellarg(arg: $this->dbServerHostname) . ' '
			. '--port=' . escapeshellarg(arg: $this->dbServerPort) . ' '
			. '--user=' . escapeshellarg(arg: $this->dbServerUsername) . ' '
			. '--password=' . escapeshellarg(arg: $this->dbServerPassword) . ' '
			. '--database=' . escapeshellarg(arg: $this->dbServerDB) . ' '
			. '--execute=' . escapeshellarg(arg: $sql);

		return $shellCommand;
	}
}
