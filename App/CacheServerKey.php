<?php

/**
 * Cache Server Key
 * php version 8.3
 *
 * @category  Cache Server Key
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/**
 * Cache Server Key
 * php version 8.3
 *
 * @category  Cache Server Key
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class CacheServerKey
{
	/**
	 * Get open to web Domain key
	 *
	 * @param string $domainName Domain Name
	 *
	 * @return string
	 */
	public static function openDomain(&$domainName): string
	{
		return "otw:{$domainName}";
	}

	/**
	 * Get closed to web Domain key
	 *
	 * @param string $domainName Domain Name
	 *
	 * @return string
	 */
	public static function authDomain($domainName): string
	{
		return "ctw:{$domainName}";
	}

	/**
	 * Get Customer user username key
	 *
	 * @param int    $customerId Customer Id
	 * @param string $username   Username
	 *
	 * @return string
	 */
	public static function customerUsername($customerId, $username): string
	{
		return "c:{$customerId}:u:{$username}";
	}

	/**
	 * Get Group key
	 *
	 * @param int $customerId Customer Id
	 * @param int $groupId    Group Id
	 *
	 * @return string
	 */
	public static function customerGroup($customerId, $groupId): string
	{
		return "c:{$customerId}:g:{$groupId}";
	}

	/**
	 * Get Customer CIDR key
	 *
	 * @param int $customerId Customer Id
	 *
	 * @return string
	 */
	public static function customerCidr($customerId): string
	{
		return "c:{$customerId}:cidr";
	}

	/**
	 * Get Customer group CIDR key
	 *
	 * @param int $customerId Customer Id
	 * @param int $groupId    Group Id
	 *
	 * @return string
	 */
	public static function customerGroupCidr($customerId, $groupId): string
	{
		return "c:{$customerId}:g:{$groupId}:cidr";
	}

	/**
	 * Get Customer user CIDR key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return string
	 */
	public static function customerUserCidr($customerId, $userId): string
	{
		return "c:{$customerId}:u:{$userId}:cidr";
	}

	/**
	 * Get Token key
	 *
	 * @param string $token Token
	 *
	 * @return string
	 */
	public static function token($token): string
	{
		return "t:{$token}";
	}

	/**
	 * Get Customer user Token key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return string
	 */
	public static function customerUserToken($customerId, $userId): string
	{
		return "c:{$customerId}:u:{$userId}:token";
	}

	/**
	 * Get Customer user Session id key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return string
	 */
	public static function customerUserSessionId($customerId, $userId): string
	{
		return "c:{$customerId}:u:{$userId}:sId";
	}

	/**
	 * Get key maintaining concurrency interval(active session) for current user
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return string
	 */
	public static function customerUserConcurrency($customerId, $userId): string
	{
		return "c:{$customerId}:u:{$userId}:con";
	}

	/**
	 * Get Customer user Referrer lag key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return string
	 */
	public static function customerUserReferrerLag($customerId, $userId): string
	{
		return "c:{$customerId}:u:{$userId}:rlag";
	}
}
