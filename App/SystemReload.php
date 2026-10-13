<?php

/**
 * Load Cache Server Keys
 * php version 8.3
 *
 * @category  SystemReload
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheServerKey;
use Microservices\App\Constant;
use Microservices\App\CommonFunction;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;

/**
 * Load Cache Server Keys
 * php version 8.3
 *
 * @category  SystemReload
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class SystemReload
{
	/**
	 * Initialize
	 * 
	 * @return void
	 */
	public static function init(): void
	{
		Env::loadEnv(
			customerId: 0
		);

		DbCommonFunction::connectGlobalCache();
		DbCommonFunction::connectGlobalDb();
	}

	/**
	 * Process
	 * 
	 * @param string $httpRequestIp Request Ip
	 * 
	 * @return bool
	 */
	public static function process(
		$httpRequestIp
	): bool {
		return self::processCustomer(
			httpRequestIp: $httpRequestIp
		);
	}

	/**
	 * Cache Customer Data
	 * 
	 * @param string   $httpRequestIp Request ip
	 * @param null|int $customerId    Customer id
	 * 
	 * @return bool
	 */
	public static function processCustomer(
		$httpRequestIp,
		$customerId = null
	): bool {
		$SYSTEM_CUSTOMER_TABLE = Env::$SYSTEM_CUSTOMER_TABLE;

		$sql = "SELECT * FROM `{$SYSTEM_CUSTOMER_TABLE}` C";
		$paramArray = [];

		if ($customerId > 0) {
			$sql = "SELECT * FROM `{$SYSTEM_CUSTOMER_TABLE}` C WHERE customer_id = :customer_id";
			$paramArray[':customer_id'] = $customerId;
		}

		DbCommonFunction::$globalDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$customerDataArray = DbCommonFunction::$globalDbObject->fetchAll();
		DbCommonFunction::$globalDbObject->closeCursor();
		foreach ($customerDataArray as $customerData) {
			$customerId = $customerData['customer_id'];
			Env::loadEnv(
				customerId: $customerId
			);
			CommonFunction::checkCidr(
				ip: $httpRequestIp,
				cidrString: Env::$SYSTEM_RELOAD_CACHE_CIDR
			);

			if (!empty(Env::$config[$customerId]->PRIVATE_API_DOMAIN_NAME)) {
				$privateApiDomainCacheKey = CacheServerKey::privateApiDomain(
					domainName: Env::$config[$customerId]->PRIVATE_API_DOMAIN_NAME
				);
				DbCommonFunction::$globalCacheObject->cacheSet(
					cacheKey: $privateApiDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if (!empty(Env::$config[$customerId]->PRIVATE_WEB_DOMAIN_NAME)) {
				$privateWebDomainCacheKey = CacheServerKey::privateWebDomain(
					domainName: Env::$config[$customerId]->PRIVATE_WEB_DOMAIN_NAME
				);
				DbCommonFunction::$globalCacheObject->cacheSet(
					cacheKey: $privateWebDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if (!empty(Env::$config[$customerId]->PUBLIC_DOMAIN_NAME)) {
				$publicDomainCacheKey = CacheServerKey::publicDomain(
					domainName: Env::$config[$customerId]->PUBLIC_DOMAIN_NAME
				);
				DbCommonFunction::$globalCacheObject->cacheSet(
					cacheKey: $publicDomainCacheKey,
					cacheValue: $customerData
				);
			}

			self::processGroup(
				httpRequestIp: $httpRequestIp,
				customerId: $customerId
			);
			self::processUser(
				httpRequestIp: $httpRequestIp,
				customerId: $customerId
			);
		}

		return Constant::$TRUE;
	}

	/**
	 * Cache Group Data
	 * 
	 * @param string   $httpRequestIp       Request ip
	 * @param null|int $customerId          Customer id
	 * @param null|int $customerUserGroupId Customer user group id
	 * 
	 * @return bool
	 */
	public static function processGroup(
		$httpRequestIp,
		$customerId,
		$customerUserGroupId = null
	): bool {
		$customerData = self::getCustomerData(
			$customerId
		);

		$cache = DbCommonFunction::connectCache(
			customerId: $customerId
		);
		$db = DbCommonFunction::connectDatabase(
			customerId: $customerId,
			fetchDbMode: 'Master'
		);

		$sql = "SELECT * FROM `{$customerData['customer_user_group_table']}` G";
		$paramArray = [];

		if ($customerUserGroupId > 0) {
			$sql = "SELECT * FROM `{$customerData['customer_user_group_table']}` G WHERE customer_user_group_id = :customer_user_group_id";
			$paramArray[':customer_user_group_id'] = $customerUserGroupId;
		}

		// Groups
		$db->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$groupDataArray = $db->fetchAll();
		$db->closeCursor();

		foreach ($groupDataArray as $groupData) {
			$g_key = CacheServerKey::customerGroup(
				customerId: $customerId,
				customerUserGroupId: $groupData['customer_user_group_id']
			);
			$cache->cacheSet(
				cacheKey: $g_key,
				cacheValue: $groupData
			);
		}

		return Constant::$TRUE;
	}

	/**
	 * Cache User Data
	 * 
	 * @param string   $httpRequestIp  Request ip
	 * @param null|int $customerId     Customer id
	 * @param null|int $customerUserId Customer user id
	 * 
	 * @return bool
	 */
	public static function processUser(
		$httpRequestIp,
		$customerId,
		$customerUserId = null
	): bool {
		$customerData = self::getCustomerData(
			$customerId
		);

		$cache = DbCommonFunction::connectCache(
			customerId: $customerId
		);
		$db = DbCommonFunction::connectDatabase(
			customerId: $customerId,
			fetchDbMode: 'Master'
		);

		$sql = "SELECT * FROM `{$customerData['customer_user_table']}` U";
		$paramArray = [];

		if ($customerUserId > 0) {
			$sql = "SELECT * FROM `{$customerData['customer_user_table']}` U WHERE customer_user_id = :customer_user_id";
			$paramArray[':customer_user_id'] = $customerUserId;
		}

		// Groups
		$db->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$userDataArray = $db->fetchAll();
		$db->closeCursor();
		foreach ($userDataArray as $userData) {
			$cu_key = CacheServerKey::customerUsername(
				customerId: $customerId,
				username: $userData['customer_user_username']
			);
			$cache->cacheSet(
				cacheKey: $cu_key,
				cacheValue: $userData
			);
		}

		return Constant::$TRUE;
	}

	/**
	 * Cache User Data
	 * 
	 * @param null|int $customerId Customer id
	 * 
	 * @return array
	 */
	public static function getCustomerData(
		$customerId
	): array {
		if (strlen($customerId) === 0) {
			return [];
		}

		$SYSTEM_CUSTOMER_TABLE = Env::$SYSTEM_CUSTOMER_TABLE;

		$sql = "SELECT * FROM `{$SYSTEM_CUSTOMER_TABLE}` C WHERE customer_id = :customer_id";
		$paramArray[':customer_id'] = $customerId;

		DbCommonFunction::$globalDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$customerData = DbCommonFunction::$globalDbObject->fetch();

		return $customerData;
	}
}