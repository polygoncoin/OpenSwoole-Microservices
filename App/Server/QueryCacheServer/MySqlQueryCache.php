<?php

/**
 * Handling Query Cache via MySql
 * php version 8.3
 *
 * @category  QueryCache
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\QueryCacheServer;

use Microservices\App\HttpStatus;
use Microservices\App\Server\QueryCacheServer\QueryCacheServerInterface;
use Microservices\App\Server\Container\Sql\MySql as QueryCache_MySql;

/**
 * Query Caching via MySql
 * php version 8.3
 *
 * @category  QueryCache_MySql
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class MySqlQueryCache implements QueryCacheServerInterface
{
	/**
	 * Query Cache Server Hostname
	 *
	 * @var null|string
	 */
	private $queryCacheServerHostname = null;

	/**
	 * Query Cache Server Port
	 *
	 * @var null|int
	 */
	private $queryCacheServerPort = null;

	/**
	 * Query Cache Server Username
	 *
	 * @var null|string
	 */
	private $queryCacheServerUsername = null;

	/**
	 * Query Cache Server Password
	 *
	 * @var null|string
	 */
	private $queryCacheServerPassword = null;

	/**
	 * Query Cache Server DB
	 *
	 * @var null|string
	 */
	private $queryCacheServerDb = null;

	/**
	 * Cache queryCacheServerTable
	 *
	 * @var null|string
	 */
	private $queryCacheServerTable = null;

	/**
	 * Query Cache Server Object
	 *
	 * @var null|QueryCache_MySql
	 */
	private $queryCacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string $queryCacheServerHostname Hostname
	 * @param int    $queryCacheServerPort     Port
	 * @param string $queryCacheServerUsername Username
	 * @param string $queryCacheServerPassword Password
	 * @param string $queryCacheServerDb       Database
	 * @param string $queryCacheServerTable    Table
	 */
	public function __construct(
		$queryCacheServerHostname,
		$queryCacheServerPort,
		$queryCacheServerUsername,
		$queryCacheServerPassword,
		$queryCacheServerDb,
		$queryCacheServerTable
	) {
		$this->queryCacheServerHostname = $queryCacheServerHostname;
		$this->queryCacheServerPort = $queryCacheServerPort;
		$this->queryCacheServerUsername = $queryCacheServerUsername;
		$this->queryCacheServerPassword = $queryCacheServerPassword;
		$this->queryCacheServerDb = $queryCacheServerDb;
		$this->queryCacheServerTable = $queryCacheServerTable;
	}

	/**
	 * Connect Query Cache
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void
	{
		if ($this->queryCacheServerObj !== null) {
			return;
		}

		try {
			$this->queryCacheServerObj = new QueryCache_MySql(
				dbServerHostname: $this->queryCacheServerHostname,
				dbServerPort: $this->queryCacheServerPort,
				dbServerUsername: $this->queryCacheServerUsername,
				dbServerPassword: $this->queryCacheServerPassword,
				dbServerDb: $this->queryCacheServerDb
			);
		} catch (\Exception $e) {
			throw new \Exception(
				message: $e->getMessage(),
				code: HttpStatus::$InternalServerError
			);
		}
	}

	/**
	 * Query Cache key exist
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheExist($queryCacheKey): mixed
	{
		$this->connect();

		$sql = "
			SELECT count(1) as count
			FROM {$this->queryCacheServerTable}
			WHERE `key` = :key
		";
		$paramArr = [':key' => $queryCacheKey];

		$this->queryCacheServerObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		$row = $this->queryCacheServerObj->fetch();
		$this->queryCacheServerObj->closeCursor();

		return $row['count'] === 1;
	}

	/**
	 * Get Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheGet($queryCacheKey): mixed
	{
		$this->connect();

		$sql = "
			SELECT value
			FROM {$this->queryCacheServerTable}
			WHERE `key` = :key
		";
		$paramArr = [':key' => $queryCacheKey];

		$this->queryCacheServerObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		if ($row = $this->queryCacheServerObj->fetch()) {
			$this->queryCacheServerObj->closeCursor();
			return $row['value'];
		}
		$this->queryCacheServerObj->closeCursor();
		return false;
	}

	/**
	 * Set cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 * @param string $value         Query Cache value
	 *
	 * @return mixed
	 */
	public function queryCacheSet($queryCacheKey, $value): mixed
	{
		$this->connect();
		$this->cacheDelete($queryCacheKey);

		$sql = "
			INSERT INTO {$this->queryCacheServerTable}
			SET `key` = :key, value = :value
		";
		$paramArr = [':key' => $queryCacheKey, ':value' => $value];

		$this->queryCacheServerObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		$this->queryCacheServerObj->closeCursor();

		return true;
	}

	/**
	 * Increment Query Cache key as per offset
	 *
	 * @param string $queryCacheKey Query Cache key
	 * @param int    $offset        Query Cache offset
	 *
	 * @return mixed
	 */
	public function queryCacheIncrement($queryCacheKey, $offset = 1): mixed
	{
		$this->connect();

		return $this->queryCacheServerObj->cacheIncrement($queryCacheKey, $offset);
	}

	/**
	 * Delete Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheDelete($queryCacheKey): mixed
	{
		$this->connect();

		$sql = "DELETE FROM {$this->queryCacheServerTable} WHERE `key` = :key";
		$paramArr = [':key' => $queryCacheKey];

		$this->queryCacheServerObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		$this->queryCacheServerObj->closeCursor();

		return true;
	}
}
