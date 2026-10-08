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
	public static $globalDbServerObject = null;

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
	 * @param string      $cacheServerHost Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUser Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDb Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 * 
	 * @return CacheServer
	 */
	public static function connectCacheServer(
		$cacheServerType,
		$cacheServerHost,
		$cacheServerPort,
		$cacheServerUser,
		$cacheServerPassword,
		$cacheServerDb,
		$cacheServerTable
	): CacheServer {
		$cacheServer = new CacheServer(
			cacheServerType: $cacheServerType,
			cacheServerHost: $cacheServerHost,
			cacheServerPort: $cacheServerPort,
			cacheServerUser: $cacheServerUser,
			cacheServerPassword: $cacheServerPassword,
			cacheServerDb: $cacheServerDb,
			cacheServerTable: $cacheServerTable
		);

		return $cacheServer;
	}

	/**
	 * Connect customer Cache based on $activeDataKey
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
			cacheServerHost: $cacheServerCred['cacheServerHost'],
			cacheServerPort: $cacheServerCred['cacheServerPort'],
			cacheServerUser: $cacheServerCred['cacheServerUser'],
			cacheServerPassword: $cacheServerCred['cacheServerPassword'],
			cacheServerDb: $cacheServerCred['cacheServerDb'],
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
			queryCacheServerHost: $queryCacheServerCred['cacheServerHost'],
			queryCacheServerPort: $queryCacheServerCred['cacheServerPort'],
			queryCacheServerUser: $queryCacheServerCred['cacheServerUser'],
			queryCacheServerPassword: $queryCacheServerCred['cacheServerPassword'],
			queryCacheServerDb: $queryCacheServerCred['cacheServerDb'],
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
			cacheServerHost: Env::$config[$customerId]->CACHE_HOST,
			cacheServerPort: Env::$config[$customerId]->CACHE_PORT,
			cacheServerUser: Env::$config[$customerId]->CACHE_USER,
			cacheServerPassword: Env::$config[$customerId]->CACHE_PASSWORD,
			cacheServerDb: Env::$config[$customerId]->CACHE_DB,
			cacheServerTable: Env::$config[$customerId]->CACHE_TABLE
		);
	}

	/**
	 * Connect Database
	 * 
	 * @param string      $dbServerType     Database Server Type
	 * @param string      $dbServerHost Database Server Hostname
	 * @param int         $dbServerPort     Database Server Port
	 * @param string      $dbServerUser Database Server Username
	 * @param string      $dbServerPassword Database Server Password
	 * @param null|string $dbServerDb Database Server Database
	 * 
	 * @return DatabaseServer
	 */
	public static function connectDatabaseServer(
		$dbServerType,
		$dbServerHost,
		$dbServerPort,
		$dbServerUser,
		$dbServerPassword,
		$dbServerDb
	): DatabaseServer {
		$dbServer = new DatabaseServer(
			dbServerType: $dbServerType,
			dbServerHost: $dbServerHost,
			dbServerPort: $dbServerPort,
			dbServerUser: $dbServerUser,
			dbServerPassword: $dbServerPassword,
			dbServerDb: $dbServerDb
		);

		return $dbServer;
	}

	/**
	 * Connect customer Database based on $activeDataKey
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
					dbServerHost: $masterDatabaseServerCred['dbServerHost'],
					dbServerPort: $masterDatabaseServerCred['dbServerPort'],
					dbServerUser: $masterDatabaseServerCred['dbServerUser'],
					dbServerPassword: $masterDatabaseServerCred['dbServerPassword'],
					dbServerDb: $masterDatabaseServerCred['dbServerDb']
				);
				break;
			case 'Slave':
				$slaveDatabaseServerCred = self::getSlaveDatabaseServerCred(
					customerId: $customerId
				);
				return self::connectDatabaseServer(
					dbServerType: $slaveDatabaseServerCred['dbServerType'],
					dbServerHost: $slaveDatabaseServerCred['dbServerHost'],
					dbServerPort: $slaveDatabaseServerCred['dbServerPort'],
					dbServerUser: $slaveDatabaseServerCred['dbServerUser'],
					dbServerPassword: $slaveDatabaseServerCred['dbServerPassword'],
					dbServerDb: $slaveDatabaseServerCred['dbServerDb']
				);
				break;
			default:
				throw new \Exception(
					message: "Invalid activeDataKey value '{$activeDataKey}'",
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

		self::$globalDbServerObject = self::connectDatabaseServer(
			dbServerType: $masterDatabaseServerCred['dbServerType'],
			dbServerHost: $masterDatabaseServerCred['dbServerHost'],
			dbServerPort: $masterDatabaseServerCred['dbServerPort'],
			dbServerUser: $masterDatabaseServerCred['dbServerUser'],
			dbServerPassword: $masterDatabaseServerCred['dbServerPassword'],
			dbServerDb: $masterDatabaseServerCred['dbServerDb']
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
			'cacheServerHost' => Env::$config[$customerId]->CACHE_HOST,
			'cacheServerPort' => Env::$config[$customerId]->CACHE_PORT,
			'cacheServerUser' => Env::$config[$customerId]->CACHE_USER,
			'cacheServerPassword' => Env::$config[$customerId]->CACHE_PASSWORD,
			'cacheServerDb' => Env::$config[$customerId]->CACHE_DB,
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
			'cacheServerHost' => Env::$config[$customerId]->QUERY_CACHE_HOST,
			'cacheServerPort' => Env::$config[$customerId]->QUERY_CACHE_PORT,
			'cacheServerUser' => Env::$config[$customerId]->QUERY_CACHE_USER,
			'cacheServerPassword' => Env::$config[$customerId]->QUERY_CACHE_PASSWORD,
			'cacheServerDb' => Env::$config[$customerId]->QUERY_CACHE_DB,
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
			'dbServerHost' => Env::$config[$customerId]->MASTER_DB_HOST,
			'dbServerPort' => Env::$config[$customerId]->MASTER_DB_PORT,
			'dbServerUser' => Env::$config[$customerId]->MASTER_DB_USER,
			'dbServerPassword' => Env::$config[$customerId]->MASTER_DB_PASSWORD,
			'dbServerDb' => Env::$config[$customerId]->MASTER_DB_NAME
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
			'dbServerHost' => Env::$config[$customerId]->SLAVE_DB_HOST,
			'dbServerPort' => Env::$config[$customerId]->SLAVE_DB_PORT,
			'dbServerUser' => Env::$config[$customerId]->SLAVE_DB_USER,
			'dbServerPassword' => Env::$config[$customerId]->SLAVE_DB_PASSWORD,
			'dbServerDb' => Env::$config[$customerId]->SLAVE_DB_NAME
		];
	}
}
