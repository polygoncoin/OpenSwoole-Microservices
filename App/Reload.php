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
	 * @return bool
	 */
	public static function process(): bool
	{
		DbCommonFunction::connectGlobalCache();
		DbCommonFunction::connectGlobalDb();

		return self::processCustomer();
	}

	/**
	 * Cache Customer Data
	 *
	 * @param null|int $customerId Customer Id
	 *
	 * @return bool
	 */
	public static function processCustomer($customerId = null): bool
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

		DbCommonFunction::$gDbServer->execDbQuery(
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
				IP: CommonFunction::getHttpRequestIp(),
				cidrString: $customerData['reloadRestrictedCidr']
			);

			if (!empty($customerData['open_api_domain'])) {
				$cacheKey = CacheServerKey::publicDomain(
					domainName: $customerData['open_api_domain']
				);
				DbCommonFunction::$gCacheServer->cacheSet(
					cacheKey: $cacheKey,
					cacheValue: json_encode(value: $customerData)
				);
			}

			$cacheKey = CacheServerKey::privateDomain(domainName: $customerData['api_domain']);
			DbCommonFunction::$gCacheServer->cacheSet(
				cacheKey: $cacheKey,
				cacheValue: json_encode(value: $customerData)
			);

			if ($customerData['allowed_cidr'] !== null) {
				$cCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $customerData['allowed_cidr']);
				if (count(value: $cCidrIpNumberRangeArr) > 0) {
					$cCidrKey = CacheServerKey::customerCidr(customerId: $customerData['id']);
					DbCommonFunction::$gCacheServer->cacheSet(
						cacheKey: $cCidrKey,
						cacheValue: json_encode(value: $cCidrIpNumberRangeArr)
					);
				}
			}

			self::processGroup($customerData);
			self::processUser($customerData);
		}

		return true;
	}

	/**
	 * Cache Group Data
	 *
	 * @param array    $customerData Customer Data
	 * @param null|int $groupId      Group Id
	 *
	 * @return bool
	 */
	public static function processGroup($customerData, $groupId = null): bool
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
		$clientDbObj->execDbQuery(
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
				$cidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $groupData['allowed_cidr']);
				if (count(value: $cidrIpNumberRangeArr) > 0) {
					$cidrKey = CacheServerKey::customerGroupCidr(
						customerId: $customerData['id'],
						groupId: $groupData['id']
					);
					$clientCacheObj->cacheSet(
						cacheKey: $cidrKey,
						cacheValue: json_encode(value: $cidrIpNumberRangeArr)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Cache User Data
	 *
	 * @param array    $customerData Customer Data
	 * @param null|int $userId       User Id
	 *
	 * @return bool
	 */
	public static function processUser($customerData, $userId = null): bool
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
		$clientDbObj->execDbQuery(
			sql: $sql,
			paramArr: $paramArr
		);
		$userDataArr = $clientDbObj->fetchAll();
		$clientDbObj->closeCursor();
		foreach ($userDataArr as $userData) {
			if ($userData['allowed_cidr'] !== null) {
				$uCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $userData['allowed_cidr']);
				if (count(value: $uCidrIpNumberRangeArr) > 0) {
					$uCidrKey = CacheServerKey::customerUserCidr(
						customerId: $customerData['id'],
						userId: $userData['id']
					);
					$clientCacheObj->cacheSet(
						cacheKey: $uCidrKey,
						cacheValue: json_encode(value: $uCidrIpNumberRangeArr)
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
