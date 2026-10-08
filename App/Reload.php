<?php

/**
 * Load Cache Server Key
 * php version 8.3
 *
 * @category  Reload
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
 * Load Cache Server Key
 * php version 8.3
 *
 * @category  Reload
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Reload
{
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
		DbCommonFunction::connectGlobalCache(
			customerId: 0
		);
		DbCommonFunction::connectGlobalDb(
			customerId: 0
		);

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
		DbCommonFunction::connectGlobalCache(
			customerId: 0
		);
		DbCommonFunction::connectGlobalDb(
			customerId: 0
		);

		$SYSTEM_CUSTOMER_TABLE = getenv(name: 'SYSTEM_CUSTOMER_TABLE');

		$sql = "SELECT * FROM `{$SYSTEM_CUSTOMER_TABLE}` C";
		$paramArray = [];

		if ($customerId > 0) {
			$sql = "SELECT * FROM `{$SYSTEM_CUSTOMER_TABLE}` C WHERE customer_id = :customer_id";
			$paramArray[':customer_id'] = $customerId;
		}

		DbCommonFunction::$globalDbServerObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$customerDataArray = DbCommonFunction::$globalDbServerObject->fetchAll();
		DbCommonFunction::$globalDbServerObject->closeCursor();
		foreach ($customerDataArray as $customerData) {
			$customerId = $customerData['customer_id'];
			Env::loadEnv(
				customerId: $customerId
			);
			CommonFunction::checkCidr(
				ip: $httpRequestIp,
				cidrString: Env::$config[$customerId]->RELOAD_CACHE_CIDR
			);

			if (!empty(Env::$config[$customerId]->PRIVATE_API_DOMAIN_NAME)) {
				$privateApiDomainCacheKey = CacheServerKey::privateApiDomain(
					domainName: Env::$config[$customerId]->PRIVATE_API_DOMAIN_NAME
				);
				DbCommonFunction::$globalCacheServerObject->cacheSet(
					cacheKey: $privateApiDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if (!empty(Env::$config[$customerId]->PRIVATE_WEB_DOMAIN_NAME)) {
				$privateWebDomainCacheKey = CacheServerKey::privateWebDomain(
					domainName: Env::$config[$customerId]->PRIVATE_WEB_DOMAIN_NAME
				);
				DbCommonFunction::$globalCacheServerObject->cacheSet(
					cacheKey: $privateWebDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if (!empty(Env::$config[$customerId]->PRIVATE_DOMAIN_NAME)) {
				$publicDomainCacheKey = CacheServerKey::publicDomain(
					domainName: Env::$config[$customerId]->PRIVATE_DOMAIN_NAME
				);
				DbCommonFunction::$globalCacheServerObject->cacheSet(
					cacheKey: $publicDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if ($customerData['customer_cidr'] !== Constant::$NULL) {
				$customerCidrIpNumberRangeArray = CommonFunction::cidrStringIpNumberRange(
					cidrString: $customerData['customer_cidr']
				);
				if (
					count(
						value: $customerCidrIpNumberRangeArray
					) > 0
				) {
					$customerCidrCacheKey = CacheServerKey::customerCidr(
						customerId: $customerId
					);
					DbCommonFunction::$globalCacheServerObject->cacheSet(
						cacheKey: $customerCidrCacheKey,
						cacheValue: $customerCidrIpNumberRangeArray
					);
				}
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

		$cacheServerObject = DbCommonFunction::connectCache(
			customerId: $customerId
		);
		$databaseServerObject = DbCommonFunction::connectDatabase(
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
		$databaseServerObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$groupDataArray = $databaseServerObject->fetchAll();
		$databaseServerObject->closeCursor();

		foreach ($groupDataArray as $groupData) {
			$g_key = CacheServerKey::customerGroup(
				customerId: $customerId,
				customerUserGroupId: $groupData['customer_user_group_id']
			);
			$cacheServerObject->cacheSet(
				cacheKey: $g_key,
				cacheValue: $groupData
			);
			if ($groupData['customer_user_group_cidr'] !== Constant::$NULL) {
				$groupCidrIpNumberRangeArray = CommonFunction::cidrStringIpNumberRange(
					cidrString: $groupData['customer_user_group_cidr']
				);
				if (
					count(
						value: $groupCidrIpNumberRangeArray
					) > 0
				) {
					$groupCidrCacheKey = CacheServerKey::customerGroupCidr(
						customerId: $customerId,
						customerUserGroupId: $groupData['customer_user_group_id']
					);
					$cacheServerObject->cacheSet(
						cacheKey: $groupCidrCacheKey,
						cacheValue: $groupCidrIpNumberRangeArray
					);
				}
			}
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

		$cacheServerObject = DbCommonFunction::connectCache(
			customerId: $customerId
		);
		$databaseServerObject = DbCommonFunction::connectDatabase(
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
		$databaseServerObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$userDataArray = $databaseServerObject->fetchAll();
		$databaseServerObject->closeCursor();
		foreach ($userDataArray as $userData) {
			if ($userData['customer_user_cidr'] !== Constant::$NULL) {
				$userCidrIpNumberRangeArray = CommonFunction::cidrStringIpNumberRange(
					cidrString: $userData['customer_user_cidr']
				);
				if (
					count(
						value: $userCidrIpNumberRangeArray
					) > 0
				) {
					$userCidrCacheKey = CacheServerKey::customerUserCidr(
						customerId: $customerId,
						customerUserId: $userData['customer_user_id']
					);
					$cacheServerObject->cacheSet(
						cacheKey: $userCidrCacheKey,
						cacheValue: $userCidrIpNumberRangeArray
					);
				}
			}
			$cu_key = CacheServerKey::customerUsername(
				customerId: $customerId,
				username: $userData['customer_user_username']
			);
			$cacheServerObject->cacheSet(
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

		DbCommonFunction::$globalDbServerObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$customerData = DbCommonFunction::$globalDbServerObject->fetch();

		return $customerData;
	}
}