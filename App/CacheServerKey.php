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
	public static function publicDomain(
		&$domainName
	): null|string {
		if (empty($domainName)) {
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
	public static function privateTokenDomain(
		$domainName
	): null|string {
		if (empty($domainName)) {
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
	public static function privateSessionDomain(
		$domainName
	): null|string {
		if (empty($domainName)) {
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
	 * @param int $customerId          Customer Id
	 * @param int $customerUserGroupId Customer User Group Id
	 * 
	 * @return null|string
	 */
	public static function customerGroup(
		$customerId,
		$customerUserGroupId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($customerUserGroupId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:g:{$customerUserGroupId}";
	}

	/**
	 * Get Customer CIDR key
	 * 
	 * @param int $customerId Customer Id
	 * 
	 * @return null|string
	 */
	public static function customerCidr(
		$customerId
	): null|string {
		if (empty($customerId)) {
			return null;
		}
		return "c:{$customerId}:cidr";
	}

	/**
	 * Get Customer group CIDR key
	 * 
	 * @param int $customerId          Customer Id
	 * @param int $customerUserGroupId Customer User Group Id
	 * 
	 * @return null|string
	 */
	public static function customerGroupCidr(
		$customerId,
		$customerUserGroupId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($customerUserGroupId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:g:{$customerUserGroupId}:cidr";
	}

	/**
	 * Get Customer user CIDR key
	 * 
	 * @param int $customerId     Customer Id
	 * @param int $customerUserId Customer User Id
	 * 
	 * @return null|string
	 */
	public static function customerUserCidr(
		$customerId,
		$customerUserId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($customerUserId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$customerUserId}:cidr";
	}

	/**
	 * Get Token key
	 * 
	 * @param string $token Token
	 * 
	 * @return null|string
	 */
	public static function token(
		$token
	): null|string {
		if (empty($token)) {
			return null;
		}
		return "t:{$token}";
	}

	/**
	 * Get Customer user Token key
	 * 
	 * @param int $customerId     Customer Id
	 * @param int $customerUserId Customer User Id
	 * 
	 * @return null|string
	 */
	public static function customerUserToken(
		$customerId,
		$customerUserId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($customerUserId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$customerUserId}:token";
	}

	/**
	 * Get Customer user Session id key
	 * 
	 * @param int $customerId     Customer Id
	 * @param int $customerUserId Customer User Id
	 * 
	 * @return null|string
	 */
	public static function customerUserSessionId(
		$customerId,
		$customerUserId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($customerUserId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$customerUserId}:sId";
	}

	/**
	 * Get key maintaining concurrency interval(active session) for current user
	 * 
	 * @param int $customerId     Customer Id
	 * @param int $customerUserId Customer User Id
	 * 
	 * @return null|string
	 */
	public static function customerUserConcurrency(
		$customerId,
		$customerUserId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($customerUserId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$customerUserId}:con";
	}

	/**
	 * Get Customer user Referrer lag key
	 * 
	 * @param int $customerId     Customer Id
	 * @param int $customerUserId Customer User Id
	 * 
	 * @return null|string
	 */
	public static function customerUserReferrerLag(
		$customerId,
		$customerUserId
	): null|string {
		if (
			strlen($customerId) === 0
			|| strlen($customerUserId) === 0
		) {
			return null;
		}
		return "c:{$customerId}:u:{$customerUserId}:rlag";
	}
}
