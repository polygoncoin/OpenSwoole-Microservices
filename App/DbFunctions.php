<?php

/**
 * DB Functions
 * php version 8.3
 *
 * @category  DbFunctions
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheServerAuthKey;
use Microservices\App\CacheServerOpenKey;
use Microservices\App\Env;
use Microservices\App\HttpRequest;
use Microservices\App\HttpStatus;
use Microservices\App\Server\CacheServer;
use Microservices\App\Server\DatabaseServer;
use Microservices\App\Server\QueryCacheServer;

/**
 * DB Functions
 * php version 8.3
 *
 * @category  DbFunctions
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DbFunctions
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

	/**
	 * Customer Master
	 *
	 * @var Object[]
	 */
	public static $masterDb = [];

	/**
	 * Customer Slave
	 *
	 * @var Object[]
	 */
	public static $slaveDb = [];

	/** Cache Connection */
	/**
	 * Global
	 *
	 * @var null|Object
	 */
	public static $gCacheServer = null;

	/**
	 * Customer Master
	 *
	 * @var Object[]
	 */
	public static $masterCache = [];

	/**
	 * Customer Slave
	 *
	 * @var Object[]
	 */
	public static $slaveCache = [];

	/**
	 * Init server connection based on $fetchFrom
	 *
	 * @param string $fetchFrom Master/Slave
	 *
	 * @return void
	 */
	public static function connectQueryCacheServer(): void
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
			queryCacheServerDB: Env::$queryCacheServerDB,
			queryCacheServerTable: Env::$queryCacheServerTable
		);

		self::$queryCacheServer = $queryCacheServer->connectQueryCacheServer();
	}

	/**
	 * Set Cache
	 *
	 * @param string      $cacheServerType     Cache Server Type
	 * @param string      $cacheServerHostname Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUsername Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDB       Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 *
	 * @return object
	 */
	public static function connectCacheServer(
		$cacheServerType,
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDB,
		$cacheServerTable
	): object {
		$cacheServer = new CacheServer(
			cacheServerType: $cacheServerType,
			cacheServerHostname: $cacheServerHostname,
			cacheServerPort: $cacheServerPort,
			cacheServerUsername: $cacheServerUsername,
			cacheServerPassword: $cacheServerPassword,
			cacheServerDB: $cacheServerDB,
			cacheServerTable: $cacheServerTable
		);

		return $cacheServer->connectCacheServer();
	}

	/**
	 * Initialize Global DB Connection
	 *
	 * @return void
	 */
	public static function connectGlobalCacheServer(): void
	{
		if (self::$gCacheServer !== null) {
			return;
		}
		self::$gCacheServer = self::connectCacheServer(
			cacheServerType: Env::$gCacheServerType,
			cacheServerHostname: Env::$gCacheServerHostname,
			cacheServerPort: Env::$gCacheServerPort,
			cacheServerUsername: Env::$gCacheServerUsername,
			cacheServerPassword: Env::$gCacheServerPassword,
			cacheServerDB: Env::$gCacheServerDB,
			cacheServerTable: Env::$gCacheServerTable
		);
	}

	/**
	 * Init server connection based on $fetchFrom
	 *
	 * @param HttpRequest $req
	 * @param string      $fetchFrom Master/Slave
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function setCacheConnection(&$req, $fetchFrom): void
	{
		if ($req->s['cDetails'] === null) {
			throw new \Exception(
				message: 'Yet to set connection params',
				code: HttpStatus::$InternalServerError
			);
		}

		// Set Database credentials
		switch ($fetchFrom) {
			case 'Master':
				if (
					isset(self::$masterCache[$req->s['cDetails']['id']])
					&& self::$masterCache[$req->s['cDetails']['id']] !== null
				) {
					return;
				}

				$masterCacheDetails = self::getCacheMasterDetails(cDetails: $req->s['cDetails']);
				self::$masterCache[$req->s['cDetails']['id']] = self::connectCacheServer(
					cacheServerType: $masterCacheDetails['cacheServerType'],
					cacheServerHostname: $masterCacheDetails['cacheServerHostname'],
					cacheServerPort: $masterCacheDetails['cacheServerPort'],
					cacheServerUsername: $masterCacheDetails['cacheServerUsername'],
					cacheServerPassword: $masterCacheDetails['cacheServerPassword'],
					cacheServerDB: $masterCacheDetails['cacheServerDB'],
					cacheServerTable: $masterCacheDetails['cacheServerTable']
				);
				break;
			case 'Slave':
				if (self::$slaveCache !== null) {
					return;
				}

				$slaveCacheDetails = self::getCacheSlaveDetails(cDetails: $req->s['cDetails']);
				self::$slaveCache[$req->s['cDetails']['id']] = self::connectCacheServer(
					cacheServerType: $slaveCacheDetails['cacheServerType'],
					cacheServerHostname: $slaveCacheDetails['cacheServerHostname'],
					cacheServerPort: $slaveCacheDetails['cacheServerPort'],
					cacheServerUsername: $slaveCacheDetails['cacheServerUsername'],
					cacheServerPassword: $slaveCacheDetails['cacheServerPassword'],
					cacheServerDB: $slaveCacheDetails['cacheServerDB'],
					cacheServerTable: $slaveCacheDetails['cacheServerTable']
				);
				break;
			default:
				throw new \Exception(
					message: "Invalid fetchFrom value '{$fetchFrom}'",
					code: HttpStatus::$InternalServerError
				);
		}

		return;
	}

	/**
	 * Set DB
	 *
	 * @param string      $dbServerType     Database Server Type
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDB       Database Server Database
	 *
	 * @return object
	 */
	public static function connectDatabaseServer(
		$dbServerType,
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDB
	): object {
		$dbServer = new DatabaseServer(
			dbServerType: $dbServerType,
			dbServerHostname: $dbServerHostname,
			dbServerPort: $dbServerPort,
			dbServerUsername: $dbServerUsername,
			dbServerPassword: $dbServerPassword,
			dbServerDB: $dbServerDB
		);

		return $dbServer->connectDatabaseServer();
	}

	/**
	 * Initialize Global DB Connection
	 *
	 * @return void
	 */
	public static function connectGlobalDatabaseServer(): void
	{
		if (self::$gDbServer !== null) {
			return;
		}
		self::$gDbServer = self::connectDatabaseServer(
			dbServerType: Env::$gDbServerType,
			dbServerHostname: Env::$gDbServerHostname,
			dbServerPort: Env::$gDbServerPort,
			dbServerUsername: Env::$gDbServerUsername,
			dbServerPassword: Env::$gDbServerPassword,
			dbServerDB: Env::$gDbServerDB
		);
	}

	/**
	 * Init server connection based on $fetchFrom
	 *
	 * @param HttpRequest $req
	 * @param string      $fetchFrom Master/Slave
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function setDbConnection(&$req, $fetchFrom): void
	{
		if ($req->s['cDetails'] === null) {
			throw new \Exception(
				message: 'Yet to set connection params',
				code: HttpStatus::$InternalServerError
			);
		}

		// Set Database credentials
		switch ($fetchFrom) {
			case 'Master':
				if (
					isset(self::$masterDb[$req->s['cDetails']['id']])
					&& self::$masterDb[$req->s['cDetails']['id']] !== null
				) {
					return;
				}

				$masterDbDetails = self::getDbMasterDetails(cDetails: $req->s['cDetails']);
				self::$masterDb[$req->s['cDetails']['id']] = self::connectDatabaseServer(
					dbServerType: $masterDbDetails['dbServerType'],
					dbServerHostname: $masterDbDetails['dbServerHostname'],
					dbServerPort: $masterDbDetails['dbServerPort'],
					dbServerUsername: $masterDbDetails['dbServerUsername'],
					dbServerPassword: $masterDbDetails['dbServerPassword'],
					dbServerDB: $masterDbDetails['dbServerDB']
				);
				break;
			case 'Slave':
				if (
					isset(self::$slaveDb[$req->s['cDetails']['id']])
					&& self::$slaveDb[$req->s['cDetails']['id']] !== null
				) {
					return;
				}

				$slaveDbDetails = self::getDbSlaveDetails(cDetails: $req->s['cDetails']);
				self::$slaveDb[$req->s['cDetails']['id']] = self::connectDatabaseServer(
					dbServerType: $slaveDbDetails['dbServerType'],
					dbServerHostname: $slaveDbDetails['dbServerHostname'],
					dbServerPort: $slaveDbDetails['dbServerPort'],
					dbServerUsername: $slaveDbDetails['dbServerUsername'],
					dbServerPassword: $slaveDbDetails['dbServerPassword'],
					dbServerDB: $slaveDbDetails['dbServerDB']
				);
				break;
			default:
				throw new \Exception(
					message: "Invalid fetchFrom value '{$fetchFrom}'",
					code: HttpStatus::$InternalServerError
				);
		}

		return;
	}

	/**
	 * Set Cache prefix key
	 *
	 * @param HttpRequest $req
	 *
	 * @return void
	 */
	public static function setCacheServerAuthKey(&$req): void
	{
		if ($req->open) {
			CacheServerOpenKey::init(cID: $req->s['cDetails']['id']);
		} else {
			CacheServerAuthKey::init(
				cID: $req->s['cDetails']['id'],
				gID: $req->s['gDetails']['id'],
				uID: $req->s['uDetails']['id']
			);
		}
	}

	/**
	 * Get Query cache
	 *
	 * @param string $cacheKey Cache Key from Queries configuration
	 *
	 * @return mixed
	 */
	public static function getQueryCache($cacheKey): mixed
	{
		self::connectQueryCacheServer();

		$json = null;
		if (self::$queryCacheServer->cacheExists(key: $cacheKey)) {
			$json = self::$queryCacheServer->getCache(key: $cacheKey);
		}

		return $json;
	}

	/**
	 * Set Query cache
	 *
	 * @param string $cacheKey Cache Key from Queries configuration
	 * @param string $json     JSON
	 *
	 * @return void
	 */
	public static function setQueryCache($cacheKey, &$json): void
	{
		self::connectQueryCacheServer();

		self::$queryCacheServer->setCache(key: $cacheKey, value: $json);
	}

	/**
	 * Delete Query Cache
	 *
	 * @param string $cacheKey Cache Key from Queries configuration
	 *
	 * @return void
	 */
	public static function delQueryCache($cacheKey): void
	{
		self::connectQueryCacheServer();

		self::$queryCacheServer->deleteCache(key: $cacheKey);
	}

	/**
	 * Returns Cache Master Server Details
	 *
	 * @param array $cDetails Customer details
	 *
	 * @return array
	 */
	public static function getCacheMasterDetails(&$cDetails): array
	{
		return [
			'cacheServerType' => getenv(name: $cDetails['master_cache_server_type']),
			'cacheServerHostname' => getenv(name: $cDetails['master_cache_server_hostname']),
			'cacheServerPort' => getenv(name: $cDetails['master_cache_server_port']),
			'cacheServerUsername' => getenv(name: $cDetails['master_cache_server_username']),
			'cacheServerPassword' => getenv(name: $cDetails['master_cache_server_password']),
			'cacheServerDB' => getenv(name: $cDetails['master_cache_server_db']),
			'cacheServerTable' => getenv(name: $cDetails['master_cache_server_table'])
		];
	}

	/**
	 * Returns Cache Slave Server Details
	 *
	 * @param array $cDetails Customer details
	 *
	 * @return array
	 */
	public static function getCacheSlaveDetails(&$cDetails): array
	{
		return [
			'cacheServerType' => getenv(name: $cDetails['slave_cache_server_type']),
			'cacheServerHostname' => getenv(name: $cDetails['slave_cache_server_hostname']),
			'cacheServerPort' => getenv(name: $cDetails['slave_cache_server_port']),
			'cacheServerUsername' => getenv(name: $cDetails['slave_cache_server_username']),
			'cacheServerPassword' => getenv(name: $cDetails['slave_cache_server_password']),
			'cacheServerDB' => getenv(name: $cDetails['slave_cache_server_db']),
			'cacheServerTable' => getenv(name: $cDetails['slave_cache_server_table'])
		];
	}

	/**
	 * Returns Db Master Server Details
	 *
	 * @param array $cDetails Customer details
	 *
	 * @return array
	 */
	public static function getDbMasterDetails(&$cDetails): array
	{
		return [
			'dbServerType' => getenv(name: $cDetails['master_db_server_type']),
			'dbServerHostname' => getenv(name: $cDetails['master_db_server_hostname']),
			'dbServerPort' => getenv(name: $cDetails['master_db_server_port']),
			'dbServerUsername' => getenv(name: $cDetails['master_db_server_username']),
			'dbServerPassword' => getenv(name: $cDetails['master_db_server_password']),
			'dbServerDB' => getenv(name: $cDetails['master_db_server_db']),
		];
	}

	/**
	 * Returns Database Slave Server Details
	 *
	 * @param array $cDetails Customer details
	 *
	 * @return array
	 */
	public static function getDbSlaveDetails(&$cDetails): array
	{
		return [
			'dbServerType' => getenv(name: $cDetails['slave_db_server_type']),
			'dbServerHostname' => getenv(name: $cDetails['slave_db_server_hostname']),
			'dbServerPort' => getenv(name: $cDetails['slave_db_server_port']),
			'dbServerUsername' => getenv(name: $cDetails['slave_db_server_username']),
			'dbServerPassword' => getenv(name: $cDetails['slave_db_server_password']),
			'dbServerDB' => getenv(name: $cDetails['slave_db_server_db']),
		];
	}
}
