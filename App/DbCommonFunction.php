<?php

/**
 * Database Common Function
 * php version 8.3
 *
 * @category  Database Common Function
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Server\CacheServer;
use Microservices\App\Server\DatabaseServer;
use Microservices\App\Server\QueryCacheServer;

/**
 * Database Common Function
 * php version 8.3
 *
 * @category  Database Common Function
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DbCommonFunction
{
	/** Database Connection */
	/**
	 * Global
	 *
	 * @var null|DatabaseServer
	 */
	public static $gDbServer = null;

	/** Cache Connection */
	/**
	 * Global
	 *
	 * @var null|CacheServer
	 */
	public static $globalCacheServerObject = null;

	/**
	 * Connect Cache
	 *
	 * @param string      $cacheServerType     Cache Server Type
	 * @param string      $cacheServerHostname Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUsername Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDatabase Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 *
	 * @return CacheServer
	 */
	public static function connectCacheServer(
		$cacheServerType,
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDatabase,
		$cacheServerTable
	): CacheServer {
		$cacheServer = new CacheServer(
			cacheServerType: $cacheServerType,
			cacheServerHostname: $cacheServerHostname,
			cacheServerPort: $cacheServerPort,
			cacheServerUsername: $cacheServerUsername,
			cacheServerPassword: $cacheServerPassword,
			cacheServerDatabase: $cacheServerDatabase,
			cacheServerTable: $cacheServerTable
		);

		return $cacheServer;
	}

	/**
	 * Connect customer Cache based on $activeRequestDataKey
	 *
	 * @param array $customerId Customer Data
	 *
	 * @return CacheServer
	 * @throws \Exception
	 */
	public static function connectCache(
		$customerId
	): CacheServer {
		$cacheServerCred = self::getCacheCred(
			customerId: $customerId
		);

		return self::connectCacheServer(
			cacheServerType: $cacheServerCred['cacheServerType'],
			cacheServerHostname: $cacheServerCred['cacheServerHostname'],
			cacheServerPort: $cacheServerCred['cacheServerPort'],
			cacheServerUsername: $cacheServerCred['cacheServerUsername'],
			cacheServerPassword: $cacheServerCred['cacheServerPassword'],
			cacheServerDatabase: $cacheServerCred['cacheServerDatabase'],
			cacheServerTable: $cacheServerCred['cacheServerTable']
		);
	}

	/**
	 * Connect query Cache
	 *
	 * @return QueryCacheServer
	 */
	public static function connectQueryCache(): QueryCacheServer
	{
		$queryCacheServerCred = self::getQueryCacheCred(
			customerId: $customerId
		);
		return new QueryCacheServer(
			queryCacheServerMode: $queryCacheServerCred['cacheServerType'],
			queryCacheServerHost: $queryCacheServerCred['cacheServerHostname'],
			queryCacheServerPort: $queryCacheServerCred['cacheServerPort'],
			queryCacheServerUser: $queryCacheServerCred['cacheServerUsername'],
			queryCacheServerPassword: $queryCacheServerCred['cacheServerPassword'],
			queryCacheServerDb: $queryCacheServerCred['cacheServerDatabase'],
			queryCacheServerTable: $queryCacheServerCred['cacheServerTable']
		);
	}

	/**
	 * Connect global Cache
	 *
	 * @param array $customerId Customer Data
	 *
	 * @return void
	 */
	public static function connectGlobalCache(
		$customerId
	): void
	{
		if (isset(Env::$config[$customerId])) {
			return;
		}
		Env::loadEnv(
			customerId: $customerId
		);
		self::$globalCacheServerObject = self::connectCacheServer(
			cacheServerType: Env::$config[$customerId]->CACHE_MODE,
			cacheServerHostname: Env::$config[$customerId]->CACHE_HOST,
			cacheServerPort: Env::$config[$customerId]->CACHE_PORT,
			cacheServerUsername: Env::$config[$customerId]->CACHE_USER,
			cacheServerPassword: Env::$config[$customerId]->CACHE_PASSWORD,
			cacheServerDatabase: Env::$config[$customerId]->CACHE_DB,
			cacheServerTable: Env::$config[$customerId]->CACHE_TABLE
		);
	}

	/**
	 * Connect Database
	 *
	 * @param string      $dbServerType     Database Server Type
	 * @param string      $dbServerHostname Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUsername Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDatabase Database Server Database
	 *
	 * @return DatabaseServer
	 */
	public static function connectDatabaseServer(
		$dbServerType,
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDatabase
	): DatabaseServer {
		$dbServer = new DatabaseServer(
			dbServerType: $dbServerType,
			dbServerHostname: $dbServerHostname,
			dbServerPort: $dbServerPort,
			dbServerUsername: $dbServerUsername,
			dbServerPassword: $dbServerPassword,
			dbServerDatabase: $dbServerDatabase
		);

		return $dbServer;
	}

	/**
	 * Connect customer Database based on $activeRequestDataKey
	 *
	 * @param int    $customerId  Customer id
	 * @param string $fetchDbMode Master/Slave
	 *
	 * @return DatabaseServer
	 * @throws \Exception
	 */
	public static function connectDatabase(
		$customerId,
		$fetchDbMode
	): DatabaseServer {
		// Set Database credentials
		switch ($fetchDbMode) {
			case 'Master':
				$masterDatabaseServerCred = self::getMasterDatabaseCred(
					customerId: $customerId
				);
				return self::connectDatabaseServer(
					dbServerType: $masterDatabaseServerCred['dbServerType'],
					dbServerHostname: $masterDatabaseServerCred['dbServerHostname'],
					dbServerPort: $masterDatabaseServerCred['dbServerPort'],
					dbServerUsername: $masterDatabaseServerCred['dbServerUsername'],
					dbServerPassword: $masterDatabaseServerCred['dbServerPassword'],
					dbServerDatabase: $masterDatabaseServerCred['dbServerDatabase']
				);
				break;
			case 'Slave':
				$slaveDatabaseServerCred = self::getSlaveDatabaseServerCred(
					customerId: $customerId
				);
				return self::connectDatabaseServer(
					dbServerType: $slaveDatabaseServerCred['dbServerType'],
					dbServerHostname: $slaveDatabaseServerCred['dbServerHostname'],
					dbServerPort: $slaveDatabaseServerCred['dbServerPort'],
					dbServerUsername: $slaveDatabaseServerCred['dbServerUsername'],
					dbServerPassword: $slaveDatabaseServerCred['dbServerPassword'],
					dbServerDatabase: $slaveDatabaseServerCred['dbServerDatabase']
				);
				break;
			default:
				throw new \Exception(
					message: "Invalid activeRequestDataKey value '{$activeRequestDataKey}'",
					code: HttpStatus::$InternalServerError
				);
		}
	}

	/**
	 * Connect global Database
	 *
	 * @param int $customerId Customer id
	 *
	 * @return void
	 */
	public static function connectGlobalDb(
		$customerId
	): void {
		// if (isset(Env::$config[$customerId])) {
		// 	return;
		// }

		$masterDatabaseServerCred = self::getMasterDatabaseCred(
			customerId: $customerId
		);

		self::$gDbServer = self::connectDatabaseServer(
			dbServerType: $masterDatabaseServerCred['dbServerType'],
			dbServerHostname: $masterDatabaseServerCred['dbServerHostname'],
			dbServerPort: $masterDatabaseServerCred['dbServerPort'],
			dbServerUsername: $masterDatabaseServerCred['dbServerUsername'],
			dbServerPassword: $masterDatabaseServerCred['dbServerPassword'],
			dbServerDatabase: $masterDatabaseServerCred['dbServerDatabase']
		);
	}

	/**
	 * Returns Cache Master Server detail
	 *
	 * @param int $customerId Customer id
	 *
	 * @return array
	 */
	public static function getCacheCred(
		$customerId
	): array {
		if (!isset(Env::$config[$customerId])) {
			Env::loadEnv(
				customerId: $customerId
			);
		}
		return [
			'cacheServerType' => Env::$config[$customerId]->CACHE_MODE,
			'cacheServerHostname' => Env::$config[$customerId]->CACHE_HOST,
			'cacheServerPort' => Env::$config[$customerId]->CACHE_PORT,
			'cacheServerUsername' => Env::$config[$customerId]->CACHE_USER,
			'cacheServerPassword' => Env::$config[$customerId]->CACHE_PASSWORD,
			'cacheServerDatabase' => Env::$config[$customerId]->CACHE_DB,
			'cacheServerTable' => Env::$config[$customerId]->CACHE_TABLE
		];
	}

	/**
	 * Returns Query Cache Server detail
	 *
	 * @param int $customerId Customer id
	 *
	 * @return array
	 */
	public static function getQueryCacheCred(
		$customerId
	): array {
		if (!isset(Env::$config[$customerId])) {
			Env::loadEnv(
				customerId: $customerId
			);
		}
		return [
			'cacheServerType' => Env::$config[$customerId]->QUERY_CACHE_MODE,
			'cacheServerHostname' => Env::$config[$customerId]->QUERY_CACHE_HOST,
			'cacheServerPort' => Env::$config[$customerId]->QUERY_CACHE_PORT,
			'cacheServerUsername' => Env::$config[$customerId]->QUERY_CACHE_USER,
			'cacheServerPassword' => Env::$config[$customerId]->QUERY_CACHE_PASSWORD,
			'cacheServerDatabase' => Env::$config[$customerId]->QUERY_CACHE_DB,
			'cacheServerTable' => Env::$config[$customerId]->QUERY_CACHE_TABLE
		];
	}

	/**
	 * Returns Database Master Server detail
	 *
	 * @param int $customerId Customer id
	 *
	 * @return array
	 */
	public static function getMasterDatabaseCred(
		$customerId
	): array {
		if (!isset(Env::$config[$customerId])) {
			Env::loadEnv(
				customerId: $customerId
			);
		}
		return [
			'dbServerType' => Env::$config[$customerId]->MASTER_DB_MODE,
			'dbServerHostname' => Env::$config[$customerId]->MASTER_DB_HOST,
			'dbServerPort' => Env::$config[$customerId]->MASTER_DB_PORT,
			'dbServerUsername' => Env::$config[$customerId]->MASTER_DB_USER,
			'dbServerPassword' => Env::$config[$customerId]->MASTER_DB_PASSWORD,
			'dbServerDatabase' => Env::$config[$customerId]->MASTER_DB_NAME
		];
	}

	/**
	 * Returns Database Slave Server detail
	 *
	 * @param int $customerId Customer id
	 *
	 * @return array
	 */
	public static function getSlaveDatabaseServerCred(
		$customerId
	): array {
		if (!isset(Env::$config[$customerId])) {
			Env::loadEnv(
				customerId: $customerId
			);
		}
		return [
			'dbServerType' => Env::$config[$customerId]->SLAVE_DB_MODE,
			'dbServerHostname' => Env::$config[$customerId]->SLAVE_DB_HOST,
			'dbServerPort' => Env::$config[$customerId]->SLAVE_DB_PORT,
			'dbServerUsername' => Env::$config[$customerId]->SLAVE_DB_USER,
			'dbServerPassword' => Env::$config[$customerId]->SLAVE_DB_PASSWORD,
			'dbServerDatabase' => Env::$config[$customerId]->SLAVE_DB_NAME
		];
	}
}
