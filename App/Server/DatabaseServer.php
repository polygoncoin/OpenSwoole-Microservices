<?php

/**
 * Database
 * php version 8.3
 *
 * @category  Database Server
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
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
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DatabaseServer
{
	/**
	 * Database Server Type
	 *
	 * @var null|string
	 */
	public $dbServerType = null;

	/**
	 * Database Server Hostname
	 *
	 * @var null|string
	 */
	public $dbServerHostname = null;

	/**
	 * Database Server Port
	 *
	 * @var null|int
	 */
	public $dbServerPort = null;

	/**
	 * Database Server Username
	 *
	 * @var null|string
	 */
	public $dbServerUsername = null;

	/**
	 * Database Server Password
	 *
	 * @var null|string
	 */
	public $dbServerPassword = null;

	/**
	 * Database Server DB
	 *
	 * @var null|string
	 */
	public $dbServerDatabase = null;

	/**
	 * Database Server Object
	 *
	 * @var null|DatabaseServerInterface
	 */
	private $dbServerObj = null;

	/**
	 * Transaction started flag
	 *
	 * @var bool
	 */
	public $beganTransaction = false;

	/**
	 * Constructor
	 *
	 * @param string      $dbServerType     Database Server Type
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDatabase Database Server Database
	 */
	public function __construct(
        $dbServerType,
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDatabase
	) {
		$this->dbServerType = $dbServerType;
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
		if ($this->dbServerObj !== null) {
			return;
		}

		if (!in_array($this->dbServerType, ['MySql', 'PostgreSql'])) {
			throw new \Exception(
				message: "Invalid Database type '{$this->dbServerType}'",
				code: HttpStatus::$InternalServerError
			);
		}

		$dbServerNS = 'Microservices\\App\\Server\\DatabaseServer\\'
            . $this->dbServerType . 'Database';

		$this->dbServerObj = new $dbServerNS(
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

        $this->dbServerObj->useDatabase();
	}

	/**
	 * Begin transaction
	 *
	 * @return void
	 */
	public function begin(): void
	{
		$this->connectDb();

		$this->beganTransaction = true;
        $this->dbServerObj->begin();
	}

	/**
	 * Commit transaction
	 *
	 * @return void
	 */
	public function commit(): void
	{
		if ($this->beganTransaction) {
			$this->beganTransaction = false;
	        $this->dbServerObj->commit();
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
			$this->beganTransaction = false;
	        $this->dbServerObj->rollBack();
		}
	}

	/**
	 * Affected row count
	 *
	 * @return bool|int
	 */
	public function affectedRowCount(): bool|int
	{
		try {
			return $this->dbServerObj->affectedRowCount();
		} catch (\Exception $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
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
	        return $this->dbServerObj->lastInsertId();
		} catch (\Exception $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
		}
		return false;
	}

	/**
	 * Execute query
	 *
	 * @param string $sql      SQL query
	 * @param array  $paramArr SQL query params
	 * @param bool   $pushPop  Push Pop result set stmt
	 *
	 * @return void
	 */
	public function execQuery($sql, $paramArr = [], $pushPop = false): void
	{
		$this->connectDb();

		try {
			$this->dbServerObj->execQuery(
				sql: $sql,
				paramArr: $paramArr,
				pushPop: $pushPop
			);
		} catch (\Exception $e) {
			if ($this->beganTransaction) {
				$this->rollBack();
			}
		}
	}

	/**
	 * Fetch row
	 *
	 * @return mixed
	 */
	public function fetch(): mixed
	{
        return $this->dbServerObj->fetch();
	}

	/**
	 * Fetch all rows
	 *
	 * @return array|bool
	 */
	public function fetchAll(): array|bool
	{
        return $this->dbServerObj->fetchAll();
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
        $this->dbServerObj->closeCursor(pushPop: $pushPop);
	}
}
