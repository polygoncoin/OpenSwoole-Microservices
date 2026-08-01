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

namespace Microservices\App\Export\Container;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Export\ExportDatabaseServerInterface;

/**
 * Export CSV MySql container.
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
	public $dbServerDatabase = null;

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
		if (
			!extension_loaded(
				extension: $requiredExtension
			)
		) {
			if (!dl(extension_filename: $requiredExtension . '.so')) {
				throw new \Exception(
					message: "Required PHP extension '{$requiredExtension}' missing"
				);
			}
		}
		$this->binaryLoc = Env::$mySqlBinaryLocationOnWebServer;
		if (
			!file_exists(
				filename: $this->binaryLoc
			)
		) {
			throw new \Exception(
				message: 'Issue: missing MySql Customer locally'
			);
		}
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
	 */
	public function init(
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDatabase
	): void {
		$this->dbServerHostname = $dbServerHostname;
		$this->dbServerPort = $dbServerPort;
		$this->dbServerUsername = $dbServerUsername;
		$this->dbServerPassword = $dbServerPassword;
		$this->dbServerDatabase = $dbServerDatabase;
	}

	/**
	 * Validate
	 * 
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function validate(
		$sql,
		$paramArray
	): void {
		if (empty($sql)) {
			throw new \Exception(
				message: 'Empty Sql query'
			);
		}

		if (
			count(
				value: $paramArray
			) === 0
		) {
			return;
		}

		//Validate parameterized query.
		if (
			substr_count(
				haystack: $sql,
				needle: ':'
			) !== count(
				value: $paramArray
			)
		) {
			throw new \Exception(
				message: 'Parameterized query has mismatch in number of params'
			);
		}

		$paramPos = [];
		foreach (
			array_keys(
				array: $paramArray
			) as $parameterisedColumn
		) {
			if (
				substr_count(
					haystack: $sql,
					needle: $parameterisedColumn
				) > 1
			) {
				throw new \Exception(
					message: 'Parameterized query has more than one '
						. "occurrence of param '{$parameterisedColumn}'"
				);
			}
			$paramPos[$parameterisedColumn] = strpos(
				haystack: $sql,
				needle: $parameterisedColumn
			);
		}
		foreach ($paramPos as $parameterisedColumn => $value) {
			if (
				substr(
					string: $sql,
					offset: $value,
					length: strlen(
						string: $parameterisedColumn
					)
				) !== $parameterisedColumn
			) {
				throw new \Exception(
					message: "Invalid param key '{$parameterisedColumn}'"
				);
			}
		}
	}

	/**
	 * Generate raw Sql query from parameterized query via PDO.
	 * 
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 * 
	 * @return string
	 * @throws \Exception
	 */
	private function generateRawSqlQuery(
		$sql,
		$paramArray
	): string {
		if (
			empty($paramArray)
			|| count(
				value: $paramArray
			) === 0
		) {
			return $sql;
		}

		$this->validate(
			sql: $sql,
			paramArray: $paramArray
		);

		//mysqli connection
		$mysqli = mysqli_connect(
			hostname: $this->dbServerHostname,
			username: $this->dbServerUsername,
			password: $this->dbServerPassword,
			db: $this->dbServerDatabase,
			port: $this->dbServerPort
		);
		if (!$mysqli) {
			throw new \Exception(
				message: 'Connection error: ' . mysqli_connect_error()
			);
		}

		//Generate bind params
		$bindParamArray = [];
		foreach ($paramArray as $parameterisedColumn => $valueArray) {
			if (
				is_array(
					value: $valueArray
				)
			) {
				$tmpParamArray = [];
				$count = 1;
				foreach ($valueArray as $value) {
					if (
						is_array(
							value: $value
						)
					) {
						throw new \Exception(
							message: "Invalid param key '{$parameterisedColumn}'"
						);
					}
					$newParameterisedColumn = $parameterisedColumn . $count++;
					if (
						in_array(
							needle: $newParameterisedColumn,
							haystack: $tmpParamArray,
							strict: Constant::$TRUE
						)
					) {
						throw new \Exception(
							message: "Invalid new param key '{$newParameterisedColumn}'"
						);
					}
					$tmpParamArray[$newParameterisedColumn] = $value;
				}
				$sql = str_replace(
					search: $parameterisedColumn,
					replace: implode(
						separator: ', ',
						array: array_keys(
							array: $tmpParamArray
						)
					),
					subject: $sql
				);
				$bindParamArray = array_merge($bindParamArray, $tmpParamArray);
			} else {
				$bindParamArray[$parameterisedColumn] = $valueArray;
			}
		}

		//Replace parameterized valueArray.
		foreach ($bindParamArray as $parameterisedColumn => $value) {
			if (
				!ctype_digit(
					text: $value
				)
			) {
				$value = "'" . mysqli_real_escape_string(
					mysql: $mysqli,
					string: $value
				) . "'";
			}
			$sql = str_replace(
				search: $parameterisedColumn,
				replace: $value,
				subject: $sql
			);
		}

		// Close mysqli connection.
		mysqli_close(
			mysql: $mysqli
		);

		return $sql;
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
		$paramArray = null
	): string {
		$sql = $this->generateRawSqlQuery(
			sql: $sql,
			paramArray: $paramArray
		);

		// Shell command.
		$shellCommand = $this->binaryLoc . ' '
			. '--host=' . escapeshellarg(arg: $this->dbServerHostname) . ' '
			. '--port=' . escapeshellarg(arg: $this->dbServerPort) . ' '
			. '--user=' . escapeshellarg(arg: $this->dbServerUsername) . ' '
			. '--password=' . escapeshellarg(arg: $this->dbServerPassword) . ' '
			. '--database=' . escapeshellarg(arg: $this->dbServerDatabase) . ' '
			. '--execute=' . escapeshellarg(arg: $sql);

		return $shellCommand;
	}
}
