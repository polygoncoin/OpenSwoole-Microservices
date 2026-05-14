<?php

/**
 * Load Cache Server Key
 * php version 8.3
 *
 * @category  Reload
 * @package   Openswoole_Microservices
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
use Microservices\App\Http;

/**
 * Load Cache Server Key
 * php version 8.3
 *
 * @category  Reload
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Reload
{
	/**
	 * HTTP object
	 *
	 * @var null|Http
	 */
	public $http = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		return true;
	}

	/**
	 * Process
	 *
	 * @return bool
	 */
	public function process(): bool
	{
		DbCommonFunction::connectGlobalCache();
		DbCommonFunction::connectGlobalDb();

		$customerTable = getenv(name: 'customerTable');
		DbCommonFunction::$gDbServer->execDbQuery(
			sql: "
				SELECT
					 *
				FROM
					`{$customerTable}` C
				",
			paramArr: []
		);
		$customerRowArr = DbCommonFunction::$gDbServer->fetchAll();
		DbCommonFunction::$gDbServer->closeCursor();
		foreach ($customerRowArr as $customerRow) {
			if (!empty($customerRow['open_api_domain'])) {
				$c_key = CacheServerKey::openDomain(
					domainName: $customerRow['open_api_domain']
				);
				DbCommonFunction::$gCacheServer->cacheSet(
					cacheKey: $c_key,
					cacheValue: json_encode(value: $customerRow)
				);
			}

			$c_key = CacheServerKey::authDomain(domainName: $customerRow['api_domain']);
			DbCommonFunction::$gCacheServer->cacheSet(
				cacheKey: $c_key,
				cacheValue: json_encode(value: $customerRow)
			);

			if ($customerRow['allowed_cidr'] !== null) {
				$cCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $customerRow['allowed_cidr']);
				if (count(value: $cCidrIpNumberRangeArr) > 0) {
					$cCidrKey = CacheServerKey::customerCidr(customerId: $customerRow['id']);
					DbCommonFunction::$gCacheServer->cacheSet(
						cacheKey: $cCidrKey,
						cacheValue: json_encode(value: $cCidrIpNumberRangeArr)
					);
				}
			}

			$clientCacheServerCred = DbCommonFunction::clientCacheServerCred(customerData: $customerRow);
			$clientCacheObj = DbCommonFunction::connectCache(
				cacheServerType: $clientCacheServerCred['cacheServerType'],
				cacheServerHostname: $clientCacheServerCred['cacheServerHostname'],
				cacheServerPort: $clientCacheServerCred['cacheServerPort'],
				cacheServerUsername: $clientCacheServerCred['cacheServerUsername'],
				cacheServerPassword: $clientCacheServerCred['cacheServerPassword'],
				cacheServerDatabase: $clientCacheServerCred['cacheServerDatabase'],
				cacheServerTable: $clientCacheServerCred['cacheServerTable']
			);

			$clientMasterDatabaseServerCred = DbCommonFunction::clientMasterDatabaseServerCred(customerData: $customerRow);
			$clientDbObj = DbCommonFunction::connectDb(
				dbServerType: $clientMasterDatabaseServerCred['dbServerType'],
				dbServerHostname: $clientMasterDatabaseServerCred['dbServerHostname'],
				dbServerPort: $clientMasterDatabaseServerCred['dbServerPort'],
				dbServerUsername: $clientMasterDatabaseServerCred['dbServerUsername'],
				dbServerPassword: $clientMasterDatabaseServerCred['dbServerPassword'],
				dbServerDatabase: $clientMasterDatabaseServerCred['dbServerDatabase']
			);

			// Groups
			$clientDbObj->execDbQuery(
				sql: "
					SELECT
						 *
					FROM
						`{$customerRow['groupsTable']}` U
					",
				paramArr: []
			);
			$groupRowArr = $clientDbObj->fetchAll();
			$clientDbObj->closeCursor();

			foreach ($groupRowArr as $groupRow) {
				$g_key = CacheServerKey::customerGroup(
					customerId: $customerRow['id'],
					groupId: $groupRow['id']
				);
				$clientCacheObj->cacheSet(
					cacheKey: $g_key,
					cacheValue: json_encode(value: $groupRow)
				);
				if ($groupRow['allowed_cidr'] !== null) {
					$cidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $groupRow['allowed_cidr']);
					if (count(value: $cidrIpNumberRangeArr) > 0) {
						$cidrKey = CacheServerKey::customerGroupCidr(
							customerId: $customerRow['id'],
							groupId: $groupRow['id']
						);
						$clientCacheObj->cacheSet(
							cacheKey: $cidrKey,
							cacheValue: json_encode(value: $cidrIpNumberRangeArr)
						);
					}
				}
			}

			// User
			$clientDbObj->execDbQuery(
				sql: "
					SELECT
						 *
					FROM
						`{$customerRow['usersTable']}` U
					",
				paramArr: []
			);
			$userRowArr = $clientDbObj->fetchAll();
			$clientDbObj->closeCursor();
			foreach ($userRowArr as $userRow) {
				if ($userRow['allowed_cidr'] !== null) {
					$uCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $userRow['allowed_cidr']);
					if (count(value: $uCidrIpNumberRangeArr) > 0) {
						$uCidrKey = CacheServerKey::customerUserCidr(
							customerId: $customerRow['id'],
							userId: $userRow['id']
						);
						$clientCacheObj->cacheSet(
							cacheKey: $uCidrKey,
							cacheValue: json_encode(value: $uCidrIpNumberRangeArr)
						);
					}
				}
				$cu_key = CacheServerKey::customerUsername(
					customerId: $customerRow['id'],
					username: $userRow['username']
				);
				$clientCacheObj->cacheSet(
					cacheKey: $cu_key,
					cacheValue: json_encode(value: $userRow)
				);
			}
		}

		return true;
	}
}
