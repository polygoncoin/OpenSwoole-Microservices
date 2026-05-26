<?php

/**
 * Cache Server Key
 * php version 8.3
 *
 * @category  Cache Server Key
 * @package   Openswoole-Microservices
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
 * @package   Openswoole-Microservices
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
	 * @return null|string
	 */
	public static function publicDomain(&$domainName): null|string
	{
		if (strlen($domainName) === 0) {
			return null;
		}
		return "pub:{$domainName}";
	}

	/**
	 * Get closed to web Domain key
	 *
	 * @param string $domainName Domain Name
	 *
	 * @return null|string
	 */
	public static function privateTokenDomain($domainName): null|string
	{
		if (strlen($domainName) === 0) {
			return null;
		}
		return "pri:t:{$domainName}";
	}

	/**
	 * Get closed to web Domain key
	 *
	 * @param string $domainName Domain Name
	 *
	 * @return null|string
	 */
	public static function privateSessionDomain($domainName): null|string
	{
		if (strlen($domainName) === 0) {
			return null;
		}
		return "pri:s:{$domainName}";
	}

	/**
	 * Get Customer user username key
	 *
	 * @param int    $customerId Customer Id
	 * @param string $username   Username
	 *
	 * @return null|string
	 */
	public static function customerUsername(
		$customerId,
		$username
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($username) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$username}";
	}

	/**
	 * Get Group key
	 *
	 * @param int $customerId Customer Id
	 * @param int $groupId    Group Id
	 *
	 * @return null|string
	 */
	public static function customerGroup(
		$customerId,
		$groupId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($groupId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:g:{$groupId}";
	}

	/**
	 * Get Customer CIDR key
	 *
	 * @param int $customerId Customer Id
	 *
	 * @return null|string
	 */
	public static function customerCidr($customerId): null|string
	{
		if (strlen($customerId) === 0) {
			return null;
		}
		return "c:{$customerId}:cidr";
	}

	/**
	 * Get Customer group CIDR key
	 *
	 * @param int $customerId Customer Id
	 * @param int $groupId    Group Id
	 *
	 * @return null|string
	 */
	public static function customerGroupCidr(
		$customerId,
		$groupId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($groupId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:g:{$groupId}:cidr";
	}

	/**
	 * Get Customer user CIDR key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return null|string
	 */
	public static function customerUserCidr(
		$customerId,
		$userId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($userId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$userId}:cidr";
	}

	/**
	 * Get Token key
	 *
	 * @param string $token Token
	 *
	 * @return null|string
	 */
	public static function token($token): null|string
	{
		if (strlen($token) === 0) {
			return null;
		}
		return "t:{$token}";
	}

	/**
	 * Get Customer user Token key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return null|string
	 */
	public static function customerUserToken(
		$customerId,
		$userId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($userId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$userId}:token";
	}

	/**
	 * Get Customer user Session id key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return null|string
	 */
	public static function customerUserSessionId(
		$customerId,
		$userId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($userId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$userId}:sId";
	}

	/**
	 * Get key maintaining concurrency interval(active session) for current user
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return null|string
	 */
	public static function customerUserConcurrency(
		$customerId,
		$userId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($userId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$userId}:con";
	}

	/**
	 * Get Customer user Referrer lag key
	 *
	 * @param int $customerId Customer Id
	 * @param int $userId     User Id
	 *
	 * @return null|string
	 */
	public static function customerUserReferrerLag(
		$customerId,
		$userId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($userId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$userId}:rlag";
	}
}
