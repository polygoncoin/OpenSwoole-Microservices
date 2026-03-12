<?php

/**
 * Handling Query Cache via PostgreSql
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

namespace Microservices\App\Servers\QueryCache;

use Microservices\App\HttpStatus;
use Microservices\App\Servers\QueryCache\QueryCacheInterface;
use Microservices\App\Servers\Containers\Sql\PostgreSql as DB_PostgreSql;

/**
 * Query Caching via PostgreSql
 * php version 8.3
 *
 * @category  QueryCache_PostgreSql
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PostgreSqlQueryCache implements QueryCacheInterface
{
	/**
	 * Cache hostname
	 *
	 * @var null|string
	 */
	private $hostname = null;

	/**
	 * Cache port
	 *
	 * @var null|int
	 */
	private $port = null;

	/**
	 * Cache password
	 *
	 * @var null|string
	 */
	private $username = null;

	/**
	 * Cache password
	 *
	 * @var null|string
	 */
	private $password = null;

	/**
	 * Cache database
	 *
	 * @var null|string
	 */
	private $db = null;

	/**
	 * Cache table
	 *
	 * @var null|string
	 */
	private $table = null;

	/**
	 * Cache connection
	 *
	 * @var null|DB_PostgreSql
	 */
	private $cacheObj = null;

	/**
	 * Constructor
	 *
	 * @param string $hostname Hostname .env string
	 * @param string $port     Port .env string
	 * @param string $username Username .env string
	 * @param string $password Password .env string
	 * @param string $db Database .env string
	 * @param string $table    Table .env string
	 */
	public function __construct(
		$hostname,
		$port,
		$username,
		$password,
		$db,
		$table
	) {
		$this->hostname = $hostname;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
		$this->db = $db;
		$this->table = $table;
	}

	/**
	 * Cache connection
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void
	{
		if ($this->cacheObj !== null) {
			return;
		}

		try {
			$this->cacheObj = new DB_PostgreSql(
				hostname: $this->hostname,
				port: $this->port,
				username: $this->username,
				password: $this->password,
				db: $this->db
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
			FROM {$this->table}
			WHERE key = :key
		";
		$params = [':key' => $key];

		$this->cacheObj->execDbQuery(sql: $sql, params: $params);
		$row = $this->cacheObj->fetch();
		$this->cacheObj->closeCursor();

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
			FROM {$this->table}
			WHERE key = :key
		";
		$params = [':key' => $key];
		$this->cacheObj->execDbQuery(sql: $sql, params: $params);
		if ($row = $this->cacheObj->fetch()) {
			$this->cacheObj->closeCursor();
			return $row['value'];
		}
		$this->cacheObj->closeCursor();
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
			INSERT INTO {$this->table}
			SET key = :value, value = :value
		";
		$params = [':key' => $key, ':value' => $value];

		$this->cacheObj->execDbQuery(sql: $sql, params: $params);
		$this->cacheObj->closeCursor();

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

		$sql = "DELETE FROM {$this->table} WHERE key = :key";
		$params = [':key' => $key];
		$this->cacheObj->execDbQuery(sql: $sql, params: $params);
		$this->cacheObj->closeCursor();

		return true;
	}
}
