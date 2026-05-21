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
use Microservices\App\CommonFunction;
use Microservices\App\DbCommonFunction;

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
	 * @param string $httpRequestIp Requst Ip
	 *
	 * @return bool
	 */
	public static function process($httpRequestIp): bool
	{
		DbCommonFunction::connectGlobalCache();
		DbCommonFunction::connectGlobalDb();

		return self::processCustomer($httpRequestIp);
	}

	/**
	 * Cache Customer Data
	 *
	 * @param string   $httpRequestIp Requst Ip
	 * @param null|int $customerId    Customer Id
	 *
	 * @return bool
	 */
	public static function processCustomer($httpRequestIp, $customerId = null): bool
	{
		DbCommonFunction::connectGlobalCache();
		DbCommonFunction::connectGlobalDb();

		$customerTable = getenv(name: 'customerTable');

		$sql = "SELECT * FROM `{$customerTable}` C";
		$paramArr = [];

		if ($customerId > 0) {
			$sql = "SELECT * FROM `{$customerTable}` C WHERE id = :id";
			$paramArr[':id'] = $customerId;
		}

		DbCommonFunction::$gDbServer->execQuery(
			sql: $sql,
			paramArr: $paramArr
		);
		$customerDataArr = DbCommonFunction::$gDbServer->fetchAll();
		DbCommonFunction::$gDbServer->closeCursor();
		foreach ($customerDataArr as $customerData) {
			if ($customerData['enableReloadRequest'] === 'No') {
				continue;
			}

			CommonFunction::checkCidr(
				IP: $httpRequestIp,
				cidrString: $customerData['reloadRestrictedCidr']
			);

			if (!empty($customerData['private_token_domain'])) {
				$privateTokenDomainCacheKey = CacheServerKey::privateTokenDomain(
					domainName: $customerData['private_token_domain']
				);
				DbCommonFunction::$gCacheServer->cacheSet(
					cacheKey: $privateTokenDomainCacheKey,
					cacheValue: json_encode(value: $customerData)
				);
			}

			if (!empty($customerData['private_session_domain'])) {
				$privateSessionDomainCacheKey = CacheServerKey::privateSessionDomain(
					domainName: $customerData['private_session_domain']
				);
				DbCommonFunction::$gCacheServer->cacheSet(
					cacheKey: $privateSessionDomainCacheKey,
					cacheValue: json_encode(value: $customerData)
				);
			}

			if (!empty($customerData['public_domain'])) {
				$publicDomainCacheKey = CacheServerKey::publicDomain(
					domainName: $customerData['public_domain']
				);
				DbCommonFunction::$gCacheServer->cacheSet(
					cacheKey: $publicDomainCacheKey,
					cacheValue: json_encode(value: $customerData)
				);
			}

			if ($customerData['allowed_cidr'] !== null) {
				$customerCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $customerData['allowed_cidr']);
				if (count(value: $customerCidrIpNumberRangeArr) > 0) {
					$customerCidrCacheKey = CacheServerKey::customerCidr(customerId: $customerData['id']);
					DbCommonFunction::$gCacheServer->cacheSet(
						cacheKey: $customerCidrCacheKey,
						cacheValue: json_encode(value: $customerCidrIpNumberRangeArr)
					);
				}
			}

			self::processGroup($httpRequestIp, $customerData);
			self::processUser($httpRequestIp, $customerData);
		}

		return true;
	}

	/**
	 * Cache Group Data
	 *
	 * @param string   $httpRequestIp Requst Ip
	 * @param array    $customerData  Customer Data
	 * @param null|int $groupId       Group Id
	 *
	 * @return bool
	 */
	public static function processGroup($httpRequestIp, $customerData, $groupId = null): bool
	{
		$clientCacheServerCred = DbCommonFunction::clientCacheServerCred(customerData: $customerData);
		$clientCacheObj = DbCommonFunction::connectCache(
			cacheServerType: $clientCacheServerCred['cacheServerType'],
			cacheServerHostname: $clientCacheServerCred['cacheServerHostname'],
			cacheServerPort: $clientCacheServerCred['cacheServerPort'],
			cacheServerUsername: $clientCacheServerCred['cacheServerUsername'],
			cacheServerPassword: $clientCacheServerCred['cacheServerPassword'],
			cacheServerDatabase: $clientCacheServerCred['cacheServerDatabase'],
			cacheServerTable: $clientCacheServerCred['cacheServerTable']
		);

		$clientMasterDatabaseServerCred = DbCommonFunction::clientMasterDatabaseServerCred(customerData: $customerData);
		$clientDbObj = DbCommonFunction::connectDb(
			dbServerType: $clientMasterDatabaseServerCred['dbServerType'],
			dbServerHostname: $clientMasterDatabaseServerCred['dbServerHostname'],
			dbServerPort: $clientMasterDatabaseServerCred['dbServerPort'],
			dbServerUsername: $clientMasterDatabaseServerCred['dbServerUsername'],
			dbServerPassword: $clientMasterDatabaseServerCred['dbServerPassword'],
			dbServerDatabase: $clientMasterDatabaseServerCred['dbServerDatabase']
		);

		$sql = "SELECT * FROM `{$customerData['groupTable']}` G";
		$paramArr = [];

		if ($groupId > 0) {
			$sql = "SELECT * FROM `{$customerData['groupTable']}` G WHERE id = :id";
			$paramArr[':id'] = $groupId;
		}

		// Groups
		$clientDbObj->execQuery(
			sql: $sql,
			paramArr: $paramArr
		);
		$groupDataArr = $clientDbObj->fetchAll();
		$clientDbObj->closeCursor();

		foreach ($groupDataArr as $groupData) {
			$g_key = CacheServerKey::customerGroup(
				customerId: $customerData['id'],
				groupId: $groupData['id']
			);
			$clientCacheObj->cacheSet(
				cacheKey: $g_key,
				cacheValue: json_encode(value: $groupData)
			);
			if ($groupData['allowed_cidr'] !== null) {
				$groupCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $groupData['allowed_cidr']);
				if (count(value: $groupCidrIpNumberRangeArr) > 0) {
					$groupCidrCacheKey = CacheServerKey::customerGroupCidr(
						customerId: $customerData['id'],
						groupId: $groupData['id']
					);
					$clientCacheObj->cacheSet(
						cacheKey: $groupCidrCacheKey,
						cacheValue: json_encode(value: $groupCidrIpNumberRangeArr)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Cache User Data
	 *
	 * @param string   $httpRequestIp Requst Ip
	 * @param array    $customerData  Customer Data
	 * @param null|int $userId        User Id
	 *
	 * @return bool
	 */
	public static function processUser($httpRequestIp, $customerData, $userId = null): bool
	{
		$clientCacheServerCred = DbCommonFunction::clientCacheServerCred(customerData: $customerData);
		$clientCacheObj = DbCommonFunction::connectCache(
			cacheServerType: $clientCacheServerCred['cacheServerType'],
			cacheServerHostname: $clientCacheServerCred['cacheServerHostname'],
			cacheServerPort: $clientCacheServerCred['cacheServerPort'],
			cacheServerUsername: $clientCacheServerCred['cacheServerUsername'],
			cacheServerPassword: $clientCacheServerCred['cacheServerPassword'],
			cacheServerDatabase: $clientCacheServerCred['cacheServerDatabase'],
			cacheServerTable: $clientCacheServerCred['cacheServerTable']
		);

		$clientMasterDatabaseServerCred = DbCommonFunction::clientMasterDatabaseServerCred(customerData: $customerData);
		$clientDbObj = DbCommonFunction::connectDb(
			dbServerType: $clientMasterDatabaseServerCred['dbServerType'],
			dbServerHostname: $clientMasterDatabaseServerCred['dbServerHostname'],
			dbServerPort: $clientMasterDatabaseServerCred['dbServerPort'],
			dbServerUsername: $clientMasterDatabaseServerCred['dbServerUsername'],
			dbServerPassword: $clientMasterDatabaseServerCred['dbServerPassword'],
			dbServerDatabase: $clientMasterDatabaseServerCred['dbServerDatabase']
		);

		$sql = "SELECT * FROM `{$customerData['userTable']}` U";
		$paramArr = [];

		if ($userId > 0) {
			$sql = "SELECT * FROM `{$customerData['userTable']}` U WHERE id = :id";
			$paramArr[':id'] = $userId;
		}

		// Groups
		$clientDbObj->execQuery(
			sql: $sql,
			paramArr: $paramArr
		);
		$userDataArr = $clientDbObj->fetchAll();
		$clientDbObj->closeCursor();
		foreach ($userDataArr as $userData) {
			if ($userData['allowed_cidr'] !== null) {
				$userCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $userData['allowed_cidr']);
				if (count(value: $userCidrIpNumberRangeArr) > 0) {
					$userCidrCacheKey = CacheServerKey::customerUserCidr(
						customerId: $customerData['id'],
						userId: $userData['id']
					);
					$clientCacheObj->cacheSet(
						cacheKey: $userCidrCacheKey,
						cacheValue: json_encode(value: $userCidrIpNumberRangeArr)
					);
				}
			}
			$cu_key = CacheServerKey::customerUsername(
				customerId: $customerData['id'],
				username: $userData['username']
			);
			$clientCacheObj->cacheSet(
				cacheKey: $cu_key,
				cacheValue: json_encode(value: $userData)
			);
		}

		return true;
	}
}
