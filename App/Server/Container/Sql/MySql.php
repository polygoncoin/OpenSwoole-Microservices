<?php

/**
 * Sql Database
 * php version 8.3
 *
 * @category  Sql
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\Sql;

use Microservices\App\HttpStatus;
use Microservices\App\Server\Container\Sql\SqlInterface;

/**
 * MySql Database
 * php version 8.3
 *
 * @category  MySql
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
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
	public $dbServerDB = null;

	/**
	 * Database Server Object
	 *
	 * @var null|\PDO
	 */
	private $dbServerObj = null;

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
	private $stmts = [];

	/**
	 * Transaction started flag
	 *
	 * @var bool
	 */
	public $beganTransaction = false;

	/**
	 * Constructor
	 *
	 * @param string      $dbServerHostname Hostname
	 * @param string      $dbServerPort     Port
	 * @param string      $dbServerUsername Username
	 * @param string      $dbServerPassword Password
	 * @param null|string $dbServerDB Database
	 */
	public function __construct(
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDB
	) {
		$this->dbServerHostname = $dbServerHostname;
		$this->dbServerPort = $dbServerPort;
		$this->dbServerUsername = $dbServerUsername;
		$this->dbServerPassword = $dbServerPassword;
		$this->dbServerDB = $dbServerDB;
	}

	/**
	 * Database Server Object
	 *
	 * @return void
	 */
	public function connect(): void
	{
		if ($this->dbServerObj !== null) {
			return;
		}

		try {
			$this->dbServerObj = new \PDO(
				dsn: "mysql:host={$this->dbServerHostname};port={$this->dbServerPort}",
				username: $this->dbServerUsername,
				password: $this->dbServerPassword,
				options: [
					\PDO::ATTR_EMULATE_PREPARES => false,
					// \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
				]
			);

			if ($this->dbServerDB !== null) {
				$this->useDatabase();
			}
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
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
			if ($this->dbServerDB !== null) {
				$this->dbServerObj->exec(statement: "USE `{$this->dbServerDB}`");
			}
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
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
			$this->dbServerObj->beginTransaction();
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
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
				$this->dbServerObj->commit();
			}
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
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
				$this->dbServerObj->rollBack();
			}
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
			}
		}
	}

	/**
	 * Affected Rows by PDO
	 *
	 * @return bool|int
	 */
	public function affectedRows(): bool|int
	{
		try {
			if ($this->stmt) {
				return (int)$this->stmt->rowCount();
			}
		} catch (\PDOException $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
			}
		}
		return false;
	}

	/**
	 * Last Insert Id by PDO
	 *
	 * @return bool|int
	 */
	public function lastInsertId(): bool|int
	{
		try {
			if (($lastInsertId = $this->dbServerObj->lastInsertId()) !== false) {
				return $lastInsertId;
			}
		} catch (\PDOException $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
			}
		}
		return false;
	}

	/**
	 * Execute Parameterized query
	 *
	 * @param string $sql     Parameterized query
	 * @param array  $params  Parameterized query params
	 * @param bool   $pushPop Push Pop result set stmt
	 *
	 * @return void
	 */
	public function execDbQuery($sql, $params = [], $pushPop = false): void
	{
		$this->connect();

		try {
			if ($pushPop && $this->stmt) {
				array_push($this->stmts, $this->stmt);
			}
			$this->stmt = $this->dbServerObj->prepare(
				query: $sql,
				options: [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
			);
			if ($this->stmt) {
				$this->stmt->execute(params: $params);
			}
		} catch (\PDOException $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
			}
		}
	}

	/**
	 * Fetch row from statement
	 *
	 * @return mixed
	 */
	public function fetch(): mixed
	{
		try {
			if ($this->stmt) {
				return $this->stmt->fetch(mode: \PDO::FETCH_ASSOC);
			}
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
			}
		}
		return false;
	}

	/**
	 * Fetch all rows from statement
	 *
	 * @return array|bool
	 */
	public function fetchAll(): array|bool
	{
		try {
			if ($this->stmt) {
				return $this->stmt->fetchAll(mode: \PDO::FETCH_ASSOC);
			}
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
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
	public function closeCursor($pushPop = false): void
	{
		try {
			if ($this->stmt) {
				$this->stmt->closeCursor();
				if ($pushPop && count(value: $this->stmts)) {
					$this->stmt = array_pop(array: $this->stmts);
				}
			}
		} catch (\PDOException $e) {
			if ((int)$this->dbServerObj->errorCode()) {
				$this->log(e: $e);
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
	private function log($e): never
	{
		throw new \Exception(
			message: $e->getMessage(),
			code: HttpStatus::$InternalServerError
		);
	}
}
