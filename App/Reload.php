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

use Microservices\App\AppTrait;
use Microservices\App\CacheServerKey;
use Microservices\App\DbCommonFunction;
use Microservices\App\CommonFunction;

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
	use AppTrait;

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

		DbCommonFunction::$gDbServer->execDbQuery(
			sql: "
				SELECT
					 *
				FROM
					`{$this->execPhpFunc(param: getenv(name: 'customerTable'))}` C
				",
			paramArr: []
		);
		$cRowArr = DbCommonFunction::$gDbServer->fetchAll();
		DbCommonFunction::$gDbServer->closeCursor();
		foreach ($cRowArr as $cRow) {
			if ($cRow['allowed_cidr'] !== null) {
				$cCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $cRow['allowed_cidr']);
				if (count(value: $cCidrIpNumberRangeArr) > 0) {
					$cCidrKey = CacheServerKey::customerCidr(cID: $cRow['id']);
					DbCommonFunction::$gCacheServer->cacheSet(
						cacheKey: $cCidrKey,
						value: json_encode(value: $cCidrIpNumberRangeArr)
					);
				}
			}
			if (!empty($cRow['open_api_domain'])) {
				$c_key = CacheServerKey::openToWebDomain(
					domainName: $cRow['open_api_domain']
				);
				DbCommonFunction::$gCacheServer->cacheSet(
					cacheKey: $c_key,
					value: json_encode(value: $cRow)
				);
			}
			$c_key = CacheServerKey::closedToWebDomain(domainName: $cRow['api_domain']);
			DbCommonFunction::$gCacheServer->cacheSet(
				cacheKey: $c_key,
				value: json_encode(value: $cRow)
			);
			$dbServerObj = DbCommonFunction::connectDb(
				dbServerType: getenv(name: $cRow['master_db_server_type']),
				dbServerHostname: getenv(name: $cRow['master_db_server_hostname']),
				dbServerPort: getenv(name: $cRow['master_db_server_port']),
				dbServerUsername: getenv(name: $cRow['master_db_server_username']),
				dbServerPassword: getenv(name: $cRow['master_db_server_password']),
				dbServerDb: getenv(name: $cRow['master_db_server_db'])
			);

			// Groups
			$dbServerObj->execDbQuery(
				sql: "
					SELECT
						 *
					FROM
						`{$cRow['groupsTable']}` U
					",
				paramArr: []
			);
			$gRowArr = $dbServerObj->fetchAll();
			$dbServerObj->closeCursor();

			foreach ($gRowArr as $gRow) {
				$g_key = CacheServerKey::customerGroup(
					cID: $cRow['id'],
					gID: $gRow['id']
				);
				DbCommonFunction::$gCacheServer->cacheSet(cacheKey: $g_key, value: json_encode(value: $gRow));
				if ($gRow['allowed_cidr'] !== null) {
					$cidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $gRow['allowed_cidr']);
					if (count(value: $cidrIpNumberRangeArr) > 0) {
						$cidrKey = CacheServerKey::customerGroupCidr(
							cID: $cRow['id'],
							gID: $gRow['id']
						);
						DbCommonFunction::$gCacheServer->cacheSet(
							cacheKey: $cidrKey,
							value: json_encode(value: $cidrIpNumberRangeArr)
						);
					}
				}
			}

			// User
			$dbServerObj->execDbQuery(
				sql: "
					SELECT
						 *
					FROM
						`{$cRow['usersTable']}` U
					",
				paramArr: []
			);
			$uRowArr = $dbServerObj->fetchAll();
			$dbServerObj->closeCursor();
			foreach ($uRowArr as $uRow) {
				if ($uRow['allowed_cidr'] !== null) {
					$uCidrIpNumberRangeArr = CommonFunction::cidrStringIpNumberRange(cidrString: $uRow['allowed_cidr']);
					if (count(value: $uCidrIpNumberRangeArr) > 0) {
						$uCidrKey = CacheServerKey::customerUserCidr(
							cID: $cRow['id'],
							uID: $uRow['id']
						);
						DbCommonFunction::$gCacheServer->cacheSet(
							cacheKey: $uCidrKey,
							value: json_encode(value: $uCidrIpNumberRangeArr)
						);
					}
				}
				$cu_key = CacheServerKey::customerUsername(
					cID: $cRow['id'],
					username: $uRow['username']
				);
				DbCommonFunction::$gCacheServer->cacheSet(
					cacheKey: $cu_key,
					value: json_encode(value: $uRow)
				);
			}
		}

		return true;
	}

	/**
	 * Remove token from cache
	 *
	 * @param string $token Token to be delete from cache
	 *
	 * @return void
	 */
	private function processToken($token): void
	{
		DbCommonFunction::$gCacheServer->cacheDelete(cacheKey: CacheServerKey::token(token: $token));
	}
}
