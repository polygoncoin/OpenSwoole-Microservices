<?php

/**
 * Handling Database via MySql
 * php version 8.3
 * 
 * @category  Database
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\DatabaseServer;

use Microservices\App\Constant;
use Microservices\App\Server\DatabaseServer\DatabaseServerInterface;
use Microservices\App\Server\Container\Sql\MySql as DB_MySql;

/**
 * MySql Database
 * php version 8.3
 * 
 * @category  Database_MySql
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MySqlDatabase implements DatabaseServerInterface
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
	private $dbServerDatabase = null;

	/**
	 * Database Server Object
	 * 
	 * @var null|DB_MySql
	 */
	private $sqlServerObject = null;

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
	public function connectDb(): void
	{
		if ($this->sqlServerObject !== Constant::$NULL) {
			return;
		}

        $this->sqlServerObject = new DB_MySql(
            dbServerHostname: $this->dbServerHostname,
            dbServerPort: $this->dbServerPort,
            dbServerUsername: $this->dbServerUsername,
            dbServerPassword: $this->dbServerPassword,
            dbServerDatabase: $this->dbServerDatabase
        );
	}

	/**
	 * Use Database
	 * 
	 * @return void
	 */
	public function useDatabase(): void
	{
		$this->connectDb();

        $this->sqlServerObject->useDatabase();
	}

	/**
	 * Begin transaction
	 * 
	 * @return void
	 */
	public function begin(): void
	{
		$this->connectDb();

		$this->beganTransaction = Constant::$TRUE;
        $this->sqlServerObject->begin();
	}

	/**
	 * Commit transaction
	 * 
	 * @return void
	 */
	public function commit(): void
	{
		if ($this->beganTransaction) {
			$this->beganTransaction = Constant::$FALSE;
			$this->sqlServerObject->commit();
		}
	}

	/**
	 * Rollback transaction
	 * 
	 * @return void
	 */
	public function rollBack(): void
	{
		if ($this->beganTransaction) {
			$this->beganTransaction = Constant::$FALSE;
			$this->sqlServerObject->rollBack();
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
			return $this->sqlServerObject->affectedRecordCount();
		} catch (\Exception $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
		}
		return Constant::$FALSE;

	}

	/**
	 * Last insert id
	 * 
	 * @return bool|int
	 */
	public function lastInsertId(): bool|int
	{
		try {
			return $this->sqlServerObject->lastInsertId();
		} catch (\Exception $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
		}
		return Constant::$FALSE;
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
		$this->connectDb();

		try {
			$this->sqlServerObject->execQuery(
				sql: $sql,
				paramArray: $paramArray,
				pushPop: $pushPop
			);
		} catch (\Exception $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
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
        return $this->sqlServerObject->fetch();
	}

	/**
	 * Fetch all rows
	 * 
	 * @return array|bool
	 */
	public function fetchAll(): array|bool
	{
        return $this->sqlServerObject->fetchAll();
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
        $this->sqlServerObject->closeCursor(
			pushPop: $pushPop
		);
	}
}
