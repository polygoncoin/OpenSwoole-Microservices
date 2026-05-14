<?php

/**
 * Database Common Function
 * php version 8.3
 *
 * @category  Database Common Function
 * @package   Openswoole_Microservices
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
use Microservices\App\Server\CacheServer\CacheServerInterface;
use Microservices\App\Server\DatabaseServer\DatabaseServerInterface;
use Microservices\App\Server\QueryCacheServer\QueryCacheServerInterface;

/**
 * Database Common Function
 * php version 8.3
 *
 * @category  Database Common Function
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
	 * @var null|QueryCacheServerInterface
	 */
	private static $queryCacheServer = null;

	/** Database Connection */
	/**
	 * Global
	 *
	 * @var null|DatabaseServerInterface
	 */
	public static $gDbServer = null;

	/** Cache Connection */
	/**
	 * Global
	 *
	 * @var null|CacheServerInterface
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
	 * @return CacheServerInterface
	 */
	public static function connectCache(
		$cacheServerType,
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDatabase,
		$cacheServerTable
	): CacheServerInterface {
		$cacheServer = new CacheServer(
			cacheServerType: $cacheServerType,
			cacheServerHostname: $cacheServerHostname,
			cacheServerPort: $cacheServerPort,
			cacheServerUsername: $cacheServerUsername,
			cacheServerPassword: $cacheServerPassword,
			cacheServerDatabase: $cacheServerDatabase,
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
			cacheServerDatabase: Env::$gCacheServerDatabase,
			cacheServerTable: Env::$gCacheServerTable
		);
	}

	/**
	 * Connect client Cache based on $fetchFrom
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return CacheServerInterface
	 * @throws \Exception
	 */
	public static function connectClientCache(&$customerData): CacheServerInterface
	{
		$clientCacheServerCred = self::clientCacheServerCred(customerData: $customerData);
		return self::connectCache(
			cacheServerType: $clientCacheServerCred['cacheServerType'],
			cacheServerHostname: $clientCacheServerCred['cacheServerHostname'],
			cacheServerPort: $clientCacheServerCred['cacheServerPort'],
			cacheServerUsername: $clientCacheServerCred['cacheServerUsername'],
			cacheServerPassword: $clientCacheServerCred['cacheServerPassword'],
			cacheServerDatabase: $clientCacheServerCred['cacheServerDatabase'],
			cacheServerTable: $clientCacheServerCred['cacheServerTable']
		);
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
			queryCacheServerDatabase: Env::$queryCacheServerDatabase,
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
	 * @param null|string $dbServerDatabase Database Server Database
	 *
	 * @return DatabaseServerInterface
	 */
	public static function connectDb(
		$dbServerType,
		$dbServerHostname,
		$dbServerPort,
		$dbServerUsername,
		$dbServerPassword,
		$dbServerDatabase
	): DatabaseServerInterface {
		$dbServer = new DatabaseServer(
			dbServerType: $dbServerType,
			dbServerHostname: $dbServerHostname,
			dbServerPort: $dbServerPort,
			dbServerUsername: $dbServerUsername,
			dbServerPassword: $dbServerPassword,
			dbServerDatabase: $dbServerDatabase
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
			dbServerDatabase: Env::$gDbServerDatabase
		);
	}

	/**
	 * Connect client Database based on $fetchFrom
	 *
	 * @param array  $customerData Customer Data
	 * @param string $fetchFrom Master/Slave
	 *
	 * @return DatabaseServerInterface
	 * @throws \Exception
	 */
	public static function connectClientDb(&$customerData, $fetchFrom): DatabaseServerInterface
	{
		// Set Database credentials
		switch ($fetchFrom) {
			case 'Master':
				$clientMasterDatabaseServerCred = self::clientMasterDatabaseServerCred(customerData: $customerData);
				return self::connectDb(
					dbServerType: $clientMasterDatabaseServerCred['dbServerType'],
					dbServerHostname: $clientMasterDatabaseServerCred['dbServerHostname'],
					dbServerPort: $clientMasterDatabaseServerCred['dbServerPort'],
					dbServerUsername: $clientMasterDatabaseServerCred['dbServerUsername'],
					dbServerPassword: $clientMasterDatabaseServerCred['dbServerPassword'],
					dbServerDatabase: $clientMasterDatabaseServerCred['dbServerDatabase']
				);
				break;
			case 'Slave':
				$clientSlaveDatabaseServerCred = self::clientSlaveDatabaseServerCred(customerData: $customerData);
				return self::connectDb(
					dbServerType: $clientSlaveDatabaseServerCred['dbServerType'],
					dbServerHostname: $clientSlaveDatabaseServerCred['dbServerHostname'],
					dbServerPort: $clientSlaveDatabaseServerCred['dbServerPort'],
					dbServerUsername: $clientSlaveDatabaseServerCred['dbServerUsername'],
					dbServerPassword: $clientSlaveDatabaseServerCred['dbServerPassword'],
					dbServerDatabase: $clientSlaveDatabaseServerCred['dbServerDatabase']
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
	 * Prepend Query Cache key
	 *
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return string
	 */
	public static function queryCachePrepend($customerId, $queryCacheKey): string
	{
		return "qc:{$customerId}:{$queryCacheKey}";
	}

	/**
	 * Get Query Cache key
	 *
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public static function queryCacheGet($customerId, $queryCacheKey): mixed
	{
		self::connectQueryCache();

		$queryCacheKey = self::queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		$json = null;
		if (self::$queryCacheServer->queryCacheExist(queryCacheKey: $queryCacheKey)) {
			$json = self::$queryCacheServer->queryCacheGet(queryCacheKey: $queryCacheKey);
		}

		return $json;
	}

	/**
	 * Increment Query Cache key counter
	 *
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return int
	 */
	public static function queryCacheIncrement($customerId, $queryCacheKey): int
	{
		self::connectQueryCache();

		$queryCacheKey = 'i:' . $queryCacheKey;
		$queryCacheKey = self::queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		return self::$queryCacheServer->queryCacheIncrement(queryCacheKey: $queryCacheKey);
	}

	/**
	 * Set Query Cache key
	 *
	 * @param int    $customerId      Customer Id
	 * @param string $queryCacheKey   Query Cache key
	 * @param string $queryCacheValue Query Cache value
	 *
	 * @return void
	 */
	public static function queryCacheSet($customerId, $queryCacheKey, &$queryCacheValue): void
	{
		self::connectQueryCache();

		$delQueryCacheKey = 'i:' . $queryCacheKey;

		$queryCacheKey = self::queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		$delQueryCacheKey = self::queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $delQueryCacheKey
		);

		self::$queryCacheServer->queryCacheSet(queryCacheKey: $queryCacheKey, queryCacheValue: $queryCacheValue);
		self::$queryCacheServer->queryCacheDelete(queryCacheKey: $delQueryCacheKey);
	}

	/**
	 * Delete Query Cache key
	 *
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return void
	 */
	public static function queryCacheDelete($customerId, $queryCacheKey): void
	{
		self::connectQueryCache();

		$queryCacheKey = self::queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		self::$queryCacheServer->queryCacheDelete(queryCacheKey: $queryCacheKey);
	}

	/**
	 * Returns Cache Master Server detail
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return array
	 */
	public static function clientCacheServerCred(&$customerData): array
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
	 * Returns Database Master Server detail
	 *
	 * @param array $customerData Customer Data
	 *
	 * @return array
	 */
	public static function clientMasterDatabaseServerCred(&$customerData): array
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
	public static function clientSlaveDatabaseServerCred(&$customerData): array
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
