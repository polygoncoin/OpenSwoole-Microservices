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
	public $dbServerDatabase = null;

	/**
	 * Database Server Object
	 *
	 * @var null|DB_MySql
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
		if ($this->dbServerObj !== null) {
			return;
		}

        $this->dbServerObj = new DB_MySql(
            $this->dbServerHostname,
            $this->dbServerPort,
            $this->dbServerUsername,
            $this->dbServerPassword,
            $this->dbServerDatabase
        );
	}

	/**
	 * Use Database
	 *
	 * @return void
	 */
	public function useDatabase(): void
	{
		$this->connect();

        $this->dbServerObj->useDatabase();
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
	public function execDbQuery($sql, $paramArr = [], $pushPop = false): void
	{
		$this->connect();

		try {
			$this->dbServerObj->execDbQuery(
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
