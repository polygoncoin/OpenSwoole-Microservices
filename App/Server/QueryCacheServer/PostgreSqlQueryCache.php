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
	 * Cache Server Hostname
	 *
	 * @var null|string
	 */
	private $cacheServerHostname = null;

	/**
	 * Cache Server Port
	 *
	 * @var null|int
	 */
	private $cacheServerPort = null;

	/**
	 * Cache Server Username
	 *
	 * @var null|string
	 */
	private $cacheServerUsername = null;

	/**
	 * Cache Server Password
	 *
	 * @var null|string
	 */
	private $cacheServerPassword = null;

	/**
	 * Cache Server DB
	 *
	 * @var null|string
	 */
	private $cacheServerDB = null;

	/**
	 * Cache cacheServerTable
	 *
	 * @var null|string
	 */
	private $cacheServerTable = null;

	/**
	 * Cache Server Object
	 *
	 * @var null|DB_PostgreSql
	 */
	private $cacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string $cacheServerHostname Hostname
	 * @param string $cacheServerPort     Port
	 * @param string $cacheServerUsername Username
	 * @param string $cacheServerPassword Password
	 * @param string $cacheServerDB       Database
	 * @param string $cacheServerTable    Table
	 */
	public function __construct(
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDB,
		$cacheServerTable
	) {
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDB = $cacheServerDB;
		$this->cacheServerTable = $cacheServerTable;
	}

	/**
	 * Cache Server Object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void
	{
		if ($this->cacheServerObj !== null) {
			return;
		}

		try {
			$this->cacheServerObj = new DB_PostgreSql(
				dbServerHostname: $this->cacheServerHostname,
				dbServerPort: $this->cacheServerPort,
				dbServerUsername: $this->cacheServerUsername,
				dbServerPassword: $this->cacheServerPassword,
				dbServerDB: $this->cacheServerDB
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
			FROM {$this->cacheServerTable}
			WHERE key = :key
		";
		$params = [':key' => $key];

		$this->cacheServerObj->execDbQuery(sql: $sql, params: $params);
		$row = $this->cacheServerObj->fetch();
		$this->cacheServerObj->closeCursor();

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
			FROM {$this->cacheServerTable}
			WHERE key = :key
		";
		$params = [':key' => $key];
		$this->cacheServerObj->execDbQuery(sql: $sql, params: $params);
		if ($row = $this->cacheServerObj->fetch()) {
			$this->cacheServerObj->closeCursor();
			return $row['value'];
		}
		$this->cacheServerObj->closeCursor();
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
			INSERT INTO {$this->cacheServerTable}
			SET key = :value, value = :value
		";
		$params = [':key' => $key, ':value' => $value];

		$this->cacheServerObj->execDbQuery(sql: $sql, params: $params);
		$this->cacheServerObj->closeCursor();

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

		$sql = "DELETE FROM {$this->cacheServerTable} WHERE key = :key";
		$params = [':key' => $key];
		$this->cacheServerObj->execDbQuery(sql: $sql, params: $params);
		$this->cacheServerObj->closeCursor();

		return true;
	}
}
