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

use Microservices\App\Constant;
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
	public $dbServerHost = null;

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
	public $dbServerUser = null;

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
	public $dbServerDb = null;

	/**
	 * Database Server Object
	 * 
	 * @var null|DatabaseServerInterface
	 */
	private $db = null;

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
	 * @param string      $dbServerHost Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUser Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDb Database Server Database
	 */
	public function __construct(
        $dbServerType,
		$dbServerHost,
		$dbServerPort,
		$dbServerUser,
		$dbServerPassword,
		$dbServerDb
	) {
		$this->dbServerType = $dbServerType;
		$this->dbServerHost = $dbServerHost;
		$this->dbServerPort = $dbServerPort;
		$this->dbServerUser = $dbServerUser;
		$this->dbServerPassword = $dbServerPassword;
		$this->dbServerDb = $dbServerDb;
	}

	/**
	 * Connect Database
	 * 
	 * @return void
	 */
	public function connectDb(): void
	{
		if ($this->db !== Constant::$NULL) {
			return;
		}

		if (
			!in_array(
				needle: $this->dbServerType,
				haystack: ['MySql', 'PostgreSql'],
				strict: Constant::$TRUE
			)
		) {
			throw new \Exception(
				message: "Invalid Database type '{$this->dbServerType}'",
				code: HttpStatus::$InternalServerError
			);
		}

		$dbServerNS = 'Microservices\\App\\Server\\DatabaseServer\\'
            . $this->dbServerType . 'Database';

		$this->db = new $dbServerNS(
			dbServerHost: $this->dbServerHost,
			dbServerPort: $this->dbServerPort,
			dbServerUser: $this->dbServerUser,
			dbServerPassword: $this->dbServerPassword,
			dbServerDb: $this->dbServerDb
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

        $this->db->useDatabase();
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
        $this->db->begin();
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
			$this->db->commit();
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
			$this->db->rollBack();
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
			return $this->db->affectedRecordCount();
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
			return $this->db->lastInsertId();
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
			$this->db->execQuery(
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
        return $this->db->fetch();
	}

	/**
	 * Fetch all rows
	 * 
	 * @return array|bool
	 */
	public function fetchAll(): array|bool
	{
        return $this->db->fetchAll();
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
        $this->db->closeCursor(
			pushPop: $pushPop
		);
	}
}
