<?php

/**
 * DB Common Function
 * php version 8.3
 *
 * @category  Db Common Function
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Env;
use Microservices\App\HttpRequest;
use Microservices\App\HttpStatus;
use Microservices\App\Server\CacheServer;
use Microservices\App\Server\DatabaseServer;
use Microservices\App\Server\QueryCacheServer;

/**
 * DB Common Function
 * php version 8.3
 *
 * @category  Db Common Function
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DbCommonFunction
{
	/**
	 * Query Cache Connection Object
	 *
	 * @var null|Object
	 */
	private static $queryCacheServer = null;

	/** Database Connection */
	/**
	 * Global
	 *
	 * @var null|Object
	 */
	public static $gDbServer = null;

	/** Cache Connection */
	/**
	 * Global
	 *
	 * @var null|Object
	 */
	public static $gCacheServer = null;

	/**
	 * Connect Cache
	 *
	 * @param string      $cacheServerType     Cache Server Type
	 * @param string      $cacheServerHostname Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUsername Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDb       Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 *
	 * @return object
	 */
	public static function connectCache(
		$cacheServerType,
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDb,
		$cacheServerTable
	): object {
		$cacheServer = new CacheServer(
			cacheServerType: $cacheServerType,
			cacheServerHostname: $cacheServerHostname,
			cacheServerPort: $cacheServerPort,
			cacheServerUsername: $cacheServerUsername,
			cacheServerPassword: $cacheServerPassword,
			cacheServerDb: $cacheServerDb,
			cacheServerTable: $cacheServerTable
		);

		return $cacheServer->connectCache();
	}

	/**
	 * Connect global Cache
	 *
	 * @return void
	 */
	public static function connectGlobalCache(): void
	{
		if (self::$gCacheServer !== null) {
			return;
		}
		self::$gCacheServer = self::connectCache(
			cacheServerType: Env::$gCacheServerType,
			cacheServerHostname: Env::$gCacheServerHostname,
			cacheServerPort: Env::$gCacheServerPort,
			cacheServerUsername: Env::$gCacheServerUsername,
			cacheServerPassword: Env::$gCacheServerPassword,
			cacheServerDb: Env::$gCacheServerDb,
			cacheServerTable: Env::$gCacheServerTable
		);
	}

	/**
	 * Connect client Cache based on $fetchFrom
	 *
	 * @param HttpRequest $req
	 * @param string      $fetchFrom Master/Slave
	 *
	 * @return object
	 * @throws \Exception
	 */
	public static function connectClientCache(&$req, $fetchFrom): object
	{
		if ($req->s['cDetail'] === null) {
			throw new \Exception(
				message: 'Yet to set connection params',
				code: HttpStatus::$InternalServerError
			);
		}

		// Set Database credentials
		switch ($fetchFrom) {
			case 'Master':
				$cacheMasterDetail = self::cacheMasterDetail(cDetail: $req->s['cDetail']);
				return self::connectCache(
					cacheServerType: $cacheMasterDetail['cacheServerType'],
					cacheServerHostname: $cacheMasterDetail['cacheServerHostname'],
					cacheServerPort: $cacheMasterDetail['cacheServerPort'],
					cacheServerUsername: $cacheMasterDetail['cacheServerUsername'],
					cacheServerPassword: $cacheMasterDetail['cacheServerPassword'],
					cacheServerDb: $cacheMasterDetail['cacheServerDb'],
					cacheServerTable: $cacheMasterDetail['cacheServerTable']
				);
				break;
			case 'Slave':
				$cacheSlaveDetail = self::cacheSlaveDetail(cDetail: $req->s['cDetail']);
				return self::connectCache(
					cacheServerType: $cacheSlaveDetail['cacheServerType'],
					cacheServerHostname: $cacheSlaveDetail['cacheServerHostname'],
					cacheServerPort: $cacheSlaveDetail['cacheServerPort'],
					cacheServerUsername: $cacheSlaveDetail['cacheServerUsername'],
					cacheServerPassword: $cacheSlaveDetail['cacheServerPassword'],
					cacheServerDb: $cacheSlaveDetail['cacheServerDb'],
					cacheServerTable: $cacheSlaveDetail['cacheServerTable']
				);
				break;
			default:
				throw new \Exception(
					message: "Invalid fetchFrom value '{$fetchFrom}'",
					code: HttpStatus::$InternalServerError
				);
		}
	}

	/**
	 * Connect query Cache
	 *
	 * @param string $fetchFrom Master/Slave
	 *
	 * @return void
	 */
	public static function connectQueryCache(): void
	{
		if (self::$queryCacheServer !== null) {
			return;
		}

		$queryCacheServer = new QueryCacheServer(
			queryCacheServerType: Env::$queryCacheServerType,
			queryCacheServerHostname: Env::$queryCacheServerHostname,
			queryCacheServerPort: Env::$queryCacheServerPort,
			queryCacheServerUsername: Env::$queryCacheServerUsername,
			queryCacheServerPassword: Env::$queryCacheServerPassword,
			queryCacheServerDb: Env::$queryCacheServerDb,
			queryCacheServerTable: Env::$queryCacheServerTable
		);

		self::$queryCacheServer = $queryCacheServer->connectQueryCache();
	}

	/**
	 * Connect Database
	 *
	 * @param string      $dbServerType     Database Server Type
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDb       Database Server Database
	 *
	 * @return object
	 */
	public static function connectDb(
		$dbServerType,
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDb
	): object {
		$dbServer = new DatabaseServer(
			dbServerType: $dbServerType,
			dbServerHostname: $dbServerHostname,
			dbServerPort: $dbServerPort,
			dbServerUsername: $dbServerUsername,
			dbServerPassword: $dbServerPassword,
			dbServerDb: $dbServerDb
		);

		return $dbServer->connectDb();
	}

	/**
	 * Connect global Database
	 *
	 * @return void
	 */
	public static function connectGlobalDb(): void
	{
		if (self::$gDbServer !== null) {
			return;
		}
		self::$gDbServer = self::connectDb(
			dbServerType: Env::$gDbServerType,
			dbServerHostname: Env::$gDbServerHostname,
			dbServerPort: Env::$gDbServerPort,
			dbServerUsername: Env::$gDbServerUsername,
			dbServerPassword: Env::$gDbServerPassword,
			dbServerDb: Env::$gDbServerDb
		);
	}

	/**
	 * Connect client Database based on $fetchFrom
	 *
	 * @param HttpRequest $req
	 * @param string      $fetchFrom Master/Slave
	 *
	 * @return object
	 * @throws \Exception
	 */
	public static function connectClientDb(&$req, $fetchFrom): object
	{
		if ($req->s['cDetail'] === null) {
			throw new \Exception(
				message: 'Yet to set connection params',
				code: HttpStatus::$InternalServerError
			);
		}

		// Set Database credentials
		switch ($fetchFrom) {
			case 'Master':
				$dbMasterDetail = self::dbMasterDetail(cDetail: $req->s['cDetail']);
				return self::connectDb(
					dbServerType: $dbMasterDetail['dbServerType'],
					dbServerHostname: $dbMasterDetail['dbServerHostname'],
					dbServerPort: $dbMasterDetail['dbServerPort'],
					dbServerUsername: $dbMasterDetail['dbServerUsername'],
					dbServerPassword: $dbMasterDetail['dbServerPassword'],
					dbServerDb: $dbMasterDetail['dbServerDb']
				);
				break;
			case 'Slave':
				$dbSlaveDetail = self::dbSlaveDetail(cDetail: $req->s['cDetail']);
				return self::connectDb(
					dbServerType: $dbSlaveDetail['dbServerType'],
					dbServerHostname: $dbSlaveDetail['dbServerHostname'],
					dbServerPort: $dbSlaveDetail['dbServerPort'],
					dbServerUsername: $dbSlaveDetail['dbServerUsername'],
					dbServerPassword: $dbSlaveDetail['dbServerPassword'],
					dbServerDb: $dbSlaveDetail['dbServerDb']
				);
				break;
			default:
				throw new \Exception(
					message: "Invalid fetchFrom value '{$fetchFrom}'",
					code: HttpStatus::$InternalServerError
				);
		}
	}

	/**
	 * Get Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public static function queryCacheGet($queryCacheKey): mixed
	{
		self::connectQueryCache();

		$json = null;
		if (self::$queryCacheServer->queryCacheExist(queryCacheKey: $queryCacheKey)) {
			$json = self::$queryCacheServer->queryCacheGet(queryCacheKey: $queryCacheKey);
		}

		return $json;
	}

	/**
	 * Increment Query Cache key counter
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return int
	 */
	public static function queryCacheIncrement($queryCacheKey): int
	{
		self::connectQueryCache();

		return self::$queryCacheServer->queryCacheIncrement(queryCacheKey: 'i:' . $queryCacheKey);
	}

	/**
	 * Set Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 * @param string $json          JSON
	 *
	 * @return void
	 */
	public static function queryCacheSet($queryCacheKey, &$json): void
	{
		self::connectQueryCache();

		self::$queryCacheServer->queryCacheSet(queryCacheKey: $queryCacheKey, value: $json);
		self::$queryCacheServer->queryCacheDelete(queryCacheKey: 'i:' . $queryCacheKey);
	}

	/**
	 * Delete Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return void
	 */
	public static function queryCacheDelete($queryCacheKey): void
	{
		self::connectQueryCache();

		self::$queryCacheServer->queryCacheDelete(queryCacheKey: $queryCacheKey);
	}

	/**
	 * Returns Cache Master Server detail
	 *
	 * @param array $cDetail Customer detail
	 *
	 * @return array
	 */
	public static function cacheMasterDetail(&$cDetail): array
	{
		return [
			'cacheServerType' => getenv(name: $cDetail['master_cache_server_type']),
			'cacheServerHostname' => getenv(name: $cDetail['master_cache_server_hostname']),
			'cacheServerPort' => getenv(name: $cDetail['master_cache_server_port']),
			'cacheServerUsername' => getenv(name: $cDetail['master_cache_server_username']),
			'cacheServerPassword' => getenv(name: $cDetail['master_cache_server_password']),
			'cacheServerDb' => getenv(name: $cDetail['master_cache_server_db']),
			'cacheServerTable' => getenv(name: $cDetail['master_cache_server_table'])
		];
	}

	/**
	 * Returns Cache Slave Server detail
	 *
	 * @param array $cDetail Customer detail
	 *
	 * @return array
	 */
	public static function cacheSlaveDetail(&$cDetail): array
	{
		return [
			'cacheServerType' => getenv(name: $cDetail['slave_cache_server_type']),
			'cacheServerHostname' => getenv(name: $cDetail['slave_cache_server_hostname']),
			'cacheServerPort' => getenv(name: $cDetail['slave_cache_server_port']),
			'cacheServerUsername' => getenv(name: $cDetail['slave_cache_server_username']),
			'cacheServerPassword' => getenv(name: $cDetail['slave_cache_server_password']),
			'cacheServerDb' => getenv(name: $cDetail['slave_cache_server_db']),
			'cacheServerTable' => getenv(name: $cDetail['slave_cache_server_table'])
		];
	}

	/**
	 * Returns Db Master Server detail
	 *
	 * @param array $cDetail Customer detail
	 *
	 * @return array
	 */
	public static function dbMasterDetail(&$cDetail): array
	{
		return [
			'dbServerType' => getenv(name: $cDetail['master_db_server_type']),
			'dbServerHostname' => getenv(name: $cDetail['master_db_server_hostname']),
			'dbServerPort' => getenv(name: $cDetail['master_db_server_port']),
			'dbServerUsername' => getenv(name: $cDetail['master_db_server_username']),
			'dbServerPassword' => getenv(name: $cDetail['master_db_server_password']),
			'dbServerDb' => getenv(name: $cDetail['master_db_server_db']),
		];
	}

	/**
	 * Returns Database Slave Server detail
	 *
	 * @param array $cDetail Customer detail
	 *
	 * @return array
	 */
	public static function dbSlaveDetail(&$cDetail): array
	{
		return [
			'dbServerType' => getenv(name: $cDetail['slave_db_server_type']),
			'dbServerHostname' => getenv(name: $cDetail['slave_db_server_hostname']),
			'dbServerPort' => getenv(name: $cDetail['slave_db_server_port']),
			'dbServerUsername' => getenv(name: $cDetail['slave_db_server_username']),
			'dbServerPassword' => getenv(name: $cDetail['slave_db_server_password']),
			'dbServerDb' => getenv(name: $cDetail['slave_db_server_db']),
		];
	}
}
