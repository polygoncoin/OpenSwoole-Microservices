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
	 * @param string   $httpRequestIp Request Ip
	 * @param null|int $customerId    Customer Id
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

		DbCommonFunction::$gDbServer->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$customerDataArray = DbCommonFunction::$gDbServer->fetchAll();
		DbCommonFunction::$gDbServer->closeCursor();
		foreach ($customerDataArray as $customerData) {
			$customerId = $customerData['customer_id'];
			Env::loadEnv(
				customerId: $customerId
			);
			CommonFunction::checkCidr(
				ip: $httpRequestIp,
				cidrString: Env::$config[$customerId]->RELOAD_CACHE_CIDR
			);

			if (!empty($customerData['customer_private_token_domain'])) {
				$privateTokenDomainCacheKey = CacheServerKey::privateTokenDomain(
					domainName: $customerData['customer_private_token_domain']
				);
				DbCommonFunction::$globalCacheServerObject->cacheSet(
					cacheKey: $privateTokenDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if (!empty($customerData['customer_private_session_domain'])) {
				$privateSessionDomainCacheKey = CacheServerKey::privateSessionDomain(
					domainName: $customerData['customer_private_session_domain']
				);
				DbCommonFunction::$globalCacheServerObject->cacheSet(
					cacheKey: $privateSessionDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if (!empty($customerData['customer_public_domain'])) {
				$publicDomainCacheKey = CacheServerKey::publicDomain(
					domainName: $customerData['customer_public_domain']
				);
				DbCommonFunction::$globalCacheServerObject->cacheSet(
					cacheKey: $publicDomainCacheKey,
					cacheValue: $customerData
				);
			}

			if ($customerData['customer_allowed_cidr'] !== Constant::$NULL) {
				$customerCidrIpNumberRangeArray = CommonFunction::cidrStringIpNumberRange(
					cidrString: $customerData['customer_allowed_cidr']
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
				customerData: $customerData
			);
			self::processUser(
				httpRequestIp: $httpRequestIp,
				customerData: $customerData
			);
		}

		return Constant::$TRUE;
	}

	/**
	 * Cache Group Data
	 *
	 * @param string   $httpRequestIp       Request Ip
	 * @param array    $customerData        Customer Data
	 * @param null|int $customerUserGroupId Customer User Group Id
	 *
	 * @return bool
	 */
	public static function processGroup(
		$httpRequestIp,
		$customerData,
		$customerUserGroupId = null
	): bool {
		$customerId = $customerData['customer_id'];

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
			if ($groupData['customer_user_group_allowed_cidr'] !== Constant::$NULL) {
				$groupCidrIpNumberRangeArray = CommonFunction::cidrStringIpNumberRange(
					cidrString: $groupData['customer_user_group_allowed_cidr']
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
	 * @param string   $httpRequestIp  Request Ip
	 * @param array    $customerData   Customer Data
	 * @param null|int $customerUserId User Id
	 *
	 * @return bool
	 */
	public static function processUser(
		$httpRequestIp,
		$customerData,
		$customerUserId = null
	): bool {
		$customerId = $customerData['customer_id'];

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
			if ($userData['customer_user_allowed_cidr'] !== Constant::$NULL) {
				$userCidrIpNumberRangeArray = CommonFunction::cidrStringIpNumberRange(
					cidrString: $userData['customer_user_allowed_cidr']
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
}
