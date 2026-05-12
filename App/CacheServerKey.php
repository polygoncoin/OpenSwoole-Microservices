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
	public static function openToWebDomain(&$domainName): string
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
	public static function closedToWebDomain($domainName): string
	{
		return "ctw:{$domainName}";
	}

	/**
	 * Get Customer user username key
	 *
	 * @param int    $cID      Customer id
	 * @param string $username Username
	 *
	 * @return string
	 */
	public static function customerUsername($cID, $username): string
	{
		return "c:{$cID}:u:{$username}";
	}

	/**
	 * Get Group key
	 *
	 * @param int $cID Customer id
	 * @param int $gID Group id
	 *
	 * @return string
	 */
	public static function customerGroup($cID, $gID): string
	{
		return "c:{$cID}:g:{$gID}";
	}

	/**
	 * Get Customer CIDR key
	 *
	 * @param int $cID Customer id
	 *
	 * @return string
	 */
	public static function customerCidr($cID): string
	{
		return "c:{$cID}:cidr";
	}

	/**
	 * Get Customer group CIDR key
	 *
	 * @param int $cID Customer id
	 * @param int $gID Group id
	 *
	 * @return string
	 */
	public static function customerGroupCidr($cID, $gID): string
	{
		return "c:{$cID}:g:{$gID}:cidr";
	}

	/**
	 * Get Customer user CIDR key
	 *
	 * @param int $cID Customer id
	 * @param int $uID User id
	 *
	 * @return string
	 */
	public static function customerUserCidr($cID, $uID): string
	{
		return "c:{$cID}:u:{$uID}:cidr";
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
	 * @param int $cID Customer id
	 * @param int $uID User id
	 *
	 * @return string
	 */
	public static function customerUserToken($cID, $uID): string
	{
		return "c:{$cID}:u:{$uID}:token";
	}

	/**
	 * Get Customer user Session id key
	 *
	 * @param int $cID Customer id
	 * @param int $uID User id
	 *
	 * @return string
	 */
	public static function customerUserSessionId($cID, $uID): string
	{
		return "c:{$cID}:u:{$uID}:sID";
	}

	/**
	 * Get key maintaining concurrency interval(active session) for current user
	 *
	 * @param int $cID Customer id
	 * @param int $uID User id
	 *
	 * @return string
	 */
	public static function customerUserConcurrency($cID, $uID): string
	{
		return "c:{$cID}:u:{$uID}:con";
	}

	/**
	 * Get Customer user Referrer lag key
	 *
	 * @param int $cID Customer id
	 * @param int $uID User id
	 *
	 * @return string
	 */
	public static function customerUserReferrerLag($cID, $uID): string
	{
		return "c:{$cID}:u:{$uID}:rlag";
	}
}
