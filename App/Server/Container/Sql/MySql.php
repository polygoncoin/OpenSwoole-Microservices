<?php

/**
 * Sql Database
 * php version 8.3
 * 
 * @category  Sql
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\Sql;

use Microservices\App\Constant;
use Microservices\App\HttpStatus;
use Microservices\App\Server\Container\Sql\SqlInterface;

/**
 * MySql Database
 * php version 8.3
 * 
 * @category  MySql
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MySql implements SqlInterface
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
	 * Database Server Object
	 * 
	 * @var null|\PDO
	 */
	private $mysqlServerObject = null;

	/**
	 * Executed query statement
	 * 
	 * @var null|\PDOStatement
	 */
	private $stmt = null;

	/**
	 * Executed query statement
	 * 
	 * @var \PDOStatement[]
	 */
	private $stmtArray = [];

	/**
	 * Transaction started flag
	 * 
	 * @var bool
	 */
	public $beganTransaction = false;

	/**
	 * Constructor
	 * 
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDatabase Database Server Database
	 */
	public function __construct(
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDatabase
	) {
		$this->dbServerHostname = $dbServerHostname;
		$this->dbServerPort = $dbServerPort;
		$this->dbServerUsername = $dbServerUsername;
		$this->dbServerPassword = $dbServerPassword;
		$this->dbServerDatabase = $dbServerDatabase;
	}

	/**
	 * Connect Database
	 * 
	 * @return void
	 */
	public function connect(): void
	{
		if ($this->mysqlServerObject !== Constant::$NULL) {
			return;
		}

		try {
			$this->mysqlServerObject = new \PDO(
				dsn: "mysql:host={$this->dbServerHostname};port={$this->dbServerPort}",
				username: $this->dbServerUsername,
				password: $this->dbServerPassword,
				options: [
					\PDO::ATTR_EMULATE_PREPARES => Constant::$FALSE,
					// \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => Constant::$FALSE
				]
			);

			if ($this->dbServerDatabase !== Constant::$NULL) {
				$this->useDatabase();
			}
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
	}

	/**
	 * Use Database
	 * 
	 * @return void
	 */
	public function useDatabase(): void
	{
		$this->connect();

		try {
			if ($this->dbServerDatabase !== Constant::$NULL) {
				$this->mysqlServerObject->exec(
					statement: "USE `{$this->dbServerDatabase}`"
				);
			}
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
				$this->rollBack();
			}
		}
	}

	/**
	 * Begin transaction
	 * 
	 * @return void
	 */
	public function begin(): void
	{
		$this->connect();

		$this->beganTransaction = true;
		try {
			$this->mysqlServerObject->beginTransaction();
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
	}

	/**
	 * Commit transaction
	 * 
	 * @return void
	 */
	public function commit(): void
	{
		try {
			if ($this->beganTransaction) {
				$this->beganTransaction = false;
				$this->mysqlServerObject->commit();
			}
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
	}

	/**
	 * Rollback transaction
	 * 
	 * @return void
	 */
	public function rollBack(): void
	{
		try {
			if ($this->beganTransaction) {
				$this->beganTransaction = false;
				$this->mysqlServerObject->rollBack();
			}
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
	}

	/**
	 * Affected record count
	 * 
	 * @return bool|int
	 */
	public function affectedRecordCount(): bool|int
	{
		try {
			if ($this->stmt) {
				return (int)$this->stmt->rowCount();
			}
		} catch (\PDOException $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
		return false;
	}

	/**
	 * Last insert id
	 * 
	 * @return bool|int
	 */
	public function lastInsertId(): bool|int
	{
		try {
			if (($lastInsertId = $this->mysqlServerObject->lastInsertId()) !== Constant::$FALSE) {
				return $lastInsertId;
			}
		} catch (\PDOException $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
		return false;
	}

	/**
	 * Execute query
	 * 
	 * @param string $sql        Sql query
	 * @param array  $paramArray Sql query params
	 * @param bool   $pushPop    Push Pop result set stmt
	 * 
	 * @return void
	 */
	public function execQuery(
		$sql,
		$paramArray = [],
		$pushPop = false
	): void {
		$this->connect();

		try {
			if (
				$pushPop
				&& $this->stmt
			) {
				array_push(
					$this->stmtArray,
					$this->stmt
				);
			}
			$this->stmt = $this->mysqlServerObject->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			if ($this->stmt) {
				if (
					is_array(
						value: $paramArray
					)
					&& count(
						value: $paramArray
					) > 0
				) {
					$this->stmt->execute(
						$paramArray
					);
				} else {
					$this->stmt->execute();
				}
			}
		} catch (\PDOException $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
	}

	/**
	 * Fetch record
	 * 
	 * @return mixed
	 */
	public function fetch(): mixed
	{
		try {
			if ($this->stmt) {
				return $this->stmt->fetch(
					mode: \PDO::FETCH_ASSOC
				);
			}
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
		return false;
	}

	/**
	 * Fetch all rows
	 * 
	 * @return array|bool
	 */
	public function fetchAll(): array|bool
	{
		try {
			if ($this->stmt) {
				return $this->stmt->fetchAll(
					mode: \PDO::FETCH_ASSOC
				);
			}
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
		return false;
	}

	/**
	 * Close statement cursor
	 * 
	 * @param bool $pushPop Push Pop result set stmt
	 * 
	 * @return void
	 */
	public function closeCursor(
		$pushPop = false
	): void {
		try {
			if ($this->stmt) {
				$this->stmt->closeCursor();
				if (
					$pushPop
					&& count(
						value: $this->stmtArray
					)
				) {
					$this->stmt = array_pop(
						array: $this->stmtArray
					);
				}
			}
		} catch (\PDOException $e) {
			if ((int)$this->mysqlServerObject->errorCode()) {
				$this->manageException(
					e: $e
				);
			}
		}
	}

	/**
	 * Log error
	 * 
	 * @param \Exception $e Exception object
	 * 
	 * @return never
	 * @throws \Exception
	 */
	private function manageException(
		$e
	): never {
		throw new \Exception(
			message: $e->getMessage(),
			code: HttpStatus::$InternalServerError
		);
	}
}
