<?php

/**
 * Handling Query Cache via PostgreSql
 * php version 8.3
 *
 * @category  QueryCache
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\QueryCacheServer;

use Microservices\App\HttpStatus;
use Microservices\App\Server\QueryCacheServer\QueryCacheInterface;
use Microservices\App\Server\Container\Sql\PostgreSql as DB_PostgreSql;

/**
 * Query Caching via PostgreSql
 * php version 8.3
 *
 * @category  QueryCache_PostgreSql
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlQueryCache implements QueryCacheInterface
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
	private $queryCacheServerDB = null;

	/**
	 * Cache queryCacheServerTable
	 *
	 * @var null|string
	 */
	private $queryCacheServerTable = null;

	/**
	 * Query Cache Server Object
	 *
	 * @var null|DB_PostgreSql
	 */
	private $queryCacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string      $queryCacheServerHostname Query Cache Server Hostname
	 * @param int         $queryCacheServerPort     Query Cache Server Port
	 * @param string      $queryCacheServerUsername Query Cache Server Username
	 * @param string      $queryCacheServerPassword Query Cache Server Password
	 * @param null|string $queryCacheServerDB       Query Cache Server Database
	 * @param null|string $queryCacheServerTable    Query Cache Server Table
	 */
	public function __construct(
		$queryCacheServerHostname,
		$queryCacheServerPort,
		$queryCacheServerUsername,
		$queryCacheServerPassword,
		$queryCacheServerDB,
		$queryCacheServerTable
	) {
		$this->queryCacheServerHostname = $queryCacheServerHostname;
		$this->queryCacheServerPort = $queryCacheServerPort;
		$this->queryCacheServerUsername = $queryCacheServerUsername;
		$this->queryCacheServerPassword = $queryCacheServerPassword;
		$this->queryCacheServerDB = $queryCacheServerDB;
		$this->queryCacheServerTable = $queryCacheServerTable;
	}

	/**
	 * Query Cache Server Object
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
			$this->queryCacheServerObj = new DB_PostgreSql(
				dbServerHostname: $this->queryCacheServerHostname,
				dbServerPort: $this->queryCacheServerPort,
				dbServerUsername: $this->queryCacheServerUsername,
				dbServerPassword: $this->queryCacheServerPassword,
				dbServerDB: $this->queryCacheServerDB
			);
		} catch (\Exception $e) {
			throw new \Exception(
				message: $e->getMessage(),
				code: HttpStatus::$InternalServerError
			);
		}
	}

	/**
	 * Checks if cache key exist
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function cacheExists($key): mixed
	{
		$this->connect();

		$sql = "
			SELECT count(1) as count
			FROM {$this->queryCacheServerTable}
			WHERE key = :key
		";
		$params = [':key' => $key];

		$this->queryCacheServerObj->execDbQuery(sql: $sql, params: $params);
		$row = $this->queryCacheServerObj->fetch();
		$this->queryCacheServerObj->closeCursor();

		return $row['count'] === 1;
	}

	/**
	 * Get cache on basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function getCache($key): mixed
	{
		$this->connect();

		$sql = "
			SELECT value
			FROM {$this->queryCacheServerTable}
			WHERE key = :key
		";
		$params = [':key' => $key];
		$this->queryCacheServerObj->execDbQuery(sql: $sql, params: $params);
		if ($row = $this->queryCacheServerObj->fetch()) {
			$this->queryCacheServerObj->closeCursor();
			return $row['value'];
		}
		$this->queryCacheServerObj->closeCursor();
		return false;
	}

	/**
	 * Set cache on basis of key
	 *
	 * @param string   $key    Cache key
	 * @param string   $value  Cache value
	 *
	 * @return mixed
	 */
	public function setCache($key, $value): mixed
	{
		$this->connect();
		$this->deleteCache($key);

		$sql = "
			INSERT INTO {$this->queryCacheServerTable}
			SET key = :value, value = :value
		";
		$params = [':key' => $key, ':value' => $value];

		$this->queryCacheServerObj->execDbQuery(sql: $sql, params: $params);
		$this->queryCacheServerObj->closeCursor();

		return true;
	}

	/**
	 * Delete basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function deleteCache($key): mixed
	{
		$this->connect();

		$sql = "DELETE FROM {$this->queryCacheServerTable} WHERE key = :key";
		$params = [':key' => $key];
		$this->queryCacheServerObj->execDbQuery(sql: $sql, params: $params);
		$this->queryCacheServerObj->closeCursor();

		return true;
	}
}
