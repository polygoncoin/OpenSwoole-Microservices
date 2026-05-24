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
	public static $gCacheServer = null;

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
	public static function connectCache(
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
			cacheServerDatabase: Env::$gCacheServerDatabase,
			cacheServerTable: Env::$gCacheServerTable
		);
	}

	/**
	 * Connect customer Cache based on $fetchFrom
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return CacheServer
	 * @throws \Exception
	 */
	public static function connectCustomerCache(&$customerData): CacheServer
	{
		$customerCacheServerCred = self::customerCacheServerCred(customerData: $customerData);
		return self::connectCache(
			cacheServerType: $customerCacheServerCred['cacheServerType'],
			cacheServerHostname: $customerCacheServerCred['cacheServerHostname'],
			cacheServerPort: $customerCacheServerCred['cacheServerPort'],
			cacheServerUsername: $customerCacheServerCred['cacheServerUsername'],
			cacheServerPassword: $customerCacheServerCred['cacheServerPassword'],
			cacheServerDatabase: $customerCacheServerCred['cacheServerDatabase'],
			cacheServerTable: $customerCacheServerCred['cacheServerTable']
		);
	}

	/**
	 * Connect query Cache
	 *
	 * @param string $fetchFrom Master/Slave
	 *
	 * @return QueryCacheServer
	 */
	public static function connectCustomerQueryCache(): QueryCacheServer
	{
		$customerQueryCacheServerCred = self::customerQueryCacheServerCred(customerData: $customerData);
		return new QueryCacheServer(
			queryCacheServerType: $customerQueryCacheServerCred['cacheServerType'],
			queryCacheServerHostname: $customerQueryCacheServerCred['cacheServerHostname'],
			queryCacheServerPort: $customerQueryCacheServerCred['cacheServerPort'],
			queryCacheServerUsername: $customerQueryCacheServerCred['cacheServerUsername'],
			queryCacheServerPassword: $customerQueryCacheServerCred['cacheServerPassword'],
			queryCacheServerDatabase: $customerQueryCacheServerCred['cacheServerDatabase'],
			queryCacheServerTable: $customerQueryCacheServerCred['cacheServerTable']
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
	public static function connectDb(
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
			dbServerDatabase: Env::$gDbServerDatabase
		);
	}

	/**
	 * Connect customer Database based on $fetchFrom
	 *
	 * @param array  $customerData Customer Data
	 * @param string $fetchFrom Master/Slave
	 *
	 * @return DatabaseServer
	 * @throws \Exception
	 */
	public static function connectCustomerDb(&$customerData, $fetchFrom): DatabaseServer
	{
		// Set Database credentials
		switch ($fetchFrom) {
			case 'Master':
				$customerMasterDatabaseServerCred = self::customerMasterDatabaseServerCred(customerData: $customerData);
				return self::connectDb(
					dbServerType: $customerMasterDatabaseServerCred['dbServerType'],
					dbServerHostname: $customerMasterDatabaseServerCred['dbServerHostname'],
					dbServerPort: $customerMasterDatabaseServerCred['dbServerPort'],
					dbServerUsername: $customerMasterDatabaseServerCred['dbServerUsername'],
					dbServerPassword: $customerMasterDatabaseServerCred['dbServerPassword'],
					dbServerDatabase: $customerMasterDatabaseServerCred['dbServerDatabase']
				);
				break;
			case 'Slave':
				$customerSlaveDatabaseServerCred = self::customerSlaveDatabaseServerCred(customerData: $customerData);
				return self::connectDb(
					dbServerType: $customerSlaveDatabaseServerCred['dbServerType'],
					dbServerHostname: $customerSlaveDatabaseServerCred['dbServerHostname'],
					dbServerPort: $customerSlaveDatabaseServerCred['dbServerPort'],
					dbServerUsername: $customerSlaveDatabaseServerCred['dbServerUsername'],
					dbServerPassword: $customerSlaveDatabaseServerCred['dbServerPassword'],
					dbServerDatabase: $customerSlaveDatabaseServerCred['dbServerDatabase']
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
	 * Returns Cache Master Server detail
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return array
	 */
	public static function customerCacheServerCred(&$customerData): array
	{
		return [
			'cacheServerType' => getenv(name: $customerData['cache_server_type']),
			'cacheServerHostname' => getenv(name: $customerData['cache_server_hostname']),
			'cacheServerPort' => getenv(name: $customerData['cache_server_port']),
			'cacheServerUsername' => getenv(name: $customerData['cache_server_username']),
			'cacheServerPassword' => getenv(name: $customerData['cache_server_password']),
			'cacheServerDatabase' => getenv(name: $customerData['cache_server_db']),
			'cacheServerTable' => getenv(name: $customerData['cache_server_table'])
		];
	}

	/**
	 * Returns Query Cache Server detail
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return array
	 */
	public static function customerQueryCacheServerCred(&$customerData): array
	{
		return [
			'cacheServerType' => getenv(name: $customerData['query_cache_server_type']),
			'cacheServerHostname' => getenv(name: $customerData['query_cache_server_hostname']),
			'cacheServerPort' => getenv(name: $customerData['query_cache_server_port']),
			'cacheServerUsername' => getenv(name: $customerData['query_cache_server_username']),
			'cacheServerPassword' => getenv(name: $customerData['query_cache_server_password']),
			'cacheServerDatabase' => getenv(name: $customerData['query_cache_server_db']),
			'cacheServerTable' => getenv(name: $customerData['query_cache_server_table'])
		];
	}

	/**
	 * Returns Database Master Server detail
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return array
	 */
	public static function customerMasterDatabaseServerCred(&$customerData): array
	{
		return [
			'dbServerType' => getenv(name: $customerData['master_db_server_type']),
			'dbServerHostname' => getenv(name: $customerData['master_db_server_hostname']),
			'dbServerPort' => getenv(name: $customerData['master_db_server_port']),
			'dbServerUsername' => getenv(name: $customerData['master_db_server_username']),
			'dbServerPassword' => getenv(name: $customerData['master_db_server_password']),
			'dbServerDatabase' => getenv(name: $customerData['master_db_server_db']),
		];
	}

	/**
	 * Returns Database Slave Server detail
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return array
	 */
	public static function customerSlaveDatabaseServerCred(&$customerData): array
	{
		return [
			'dbServerType' => getenv(name: $customerData['slave_db_server_type']),
			'dbServerHostname' => getenv(name: $customerData['slave_db_server_hostname']),
			'dbServerPort' => getenv(name: $customerData['slave_db_server_port']),
			'dbServerUsername' => getenv(name: $customerData['slave_db_server_username']),
			'dbServerPassword' => getenv(name: $customerData['slave_db_server_password']),
			'dbServerDatabase' => getenv(name: $customerData['slave_db_server_db']),
		];
	}
}
