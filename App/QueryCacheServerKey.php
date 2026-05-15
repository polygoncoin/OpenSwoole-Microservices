<?php

/**
 * Query Cache Server Key
 * php version 8.3
 *
 * @category  Query Cache Server Key
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/**
 * Query Cache Server Key
 * php version 8.3
 *
 * @category  Query Cache Server Key
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class QueryCacheServerKey
{
	/**
	 * Public Web App key
	 *
	 * @var string
	 */
	public static $oApp = 'o:app';

	/**
	 * Private App key
	 *
	 * @var string
	 */
	public static $aApp = 'a:app';

	/**
	 * Query Cache Customer key
	 *
	 * @param int  $customerId    Customer Id
	 * @param bool $isAuthRequest
	 *
	 * @return string
	 */
	public static function customer(
		$customerId,
		$isAuthRequest = false
	): string
	{
		$appKey = self::appKey(isAuthRequest: $isAuthRequest);
		$customerKey = self::customerKey(customerId: $customerId);

		return $appKey . $customerKey;
	}

	/**
	 * Query Cache Customer Group key
	 *
	 * @param int  $customerId    Customer Id
	 * @param int  $groupId       Group Id
	 * @param bool $isAuthRequest
	 *
	 * @return string
	 */
	public static function group(
		$customerId = null,
		$groupId = null,
		$isAuthRequest = false
	): string
	{
		$appKey = self::appKey(isAuthRequest: $isAuthRequest);
		$customerKey = self::customerKey(customerId: $customerId);
		$groupKey = self::groupKey(groupId: $groupId);

		return $appKey . $customerKey . $groupKey;
	}

	/**
	 * Query Cache Customer Group User key
	 *
	 * @param int  $customerId    Customer Id
	 * @param int  $groupId       Group Id
	 * @param int  $userId        User Id
	 * @param bool $isAuthRequest
	 *
	 * @return string
	 */
	public static function user(
		$customerId = null,
		$groupId = null,
		$userId = null,
		$isAuthRequest = false
	): string
	{
		$appKey = self::appKey(isAuthRequest: $isAuthRequest);
		$customerKey = self::customerKey(customerId: $customerId);
		$groupKey = self::groupKey(groupId: $groupId);
		$userKey = self::userKey(userId: $userId);

		return $appKey . $customerKey . $groupKey . $userKey;
	}

	/**
	 * Category
	 *
	 * @param int  $customerId    Customer Id
	 * @param int  $groupId       Group Id
	 * @param bool $isAuthRequest
	 *
	 * @return string
	 */
	public static function category(
		$customerId = null,
		$groupId = null,
		$isAuthRequest = false
	): string
	{
		$appKey = self::appKey(isAuthRequest: $isAuthRequest);
		$customerKey = self::customerKey(customerId: $customerId);
		$groupKey = self::groupKey(groupId: $groupId);

		return $appKey . $customerKey . $groupKey . ':category';
	}

	/**
	 * Category1
	 *
	 * @param int  $customerId    Customer Id
	 * @param int  $groupId       Group Id
	 * @param bool $isAuthRequest
	 *
	 * @return string
	 */
	public static function category1(
		$customerId = null,
		$groupId = null,
		$isAuthRequest = false
	): string
	{
		$appKey = self::appKey(isAuthRequest: $isAuthRequest);
		$customerKey = self::customerKey(customerId: $customerId);
		$groupKey = self::groupKey(groupId: $groupId);

		return $appKey . $customerKey . $groupKey . ':category:1';
	}

	/**
	 * Set application key
	 *
	 * @param bool $isAuthRequest
	 *
	 * @return string
	 */
	private static function appKey($isAuthRequest = false): string
	{
		return $isAuthRequest ? self::$oApp : self::$aApp;
	}

	/**
	 * Query Cache Customer key
	 *
	 * @param int $customerId Customer Id
	 *
	 * @return string
	 */
	private static function customerKey($customerId): string
	{
		return $customerId !== null ? ":c:{$customerId}" : '';
	}

	/**
	 * Query Cache Group key
	 *
	 * @param int $groupId Group Id
	 *
	 * @return string
	 */
	private static function groupKey($groupId): string
	{
		return $groupId !== null ? ":g:{$groupId}" : '';
	}

	/**
	 * Query Cache User key
	 *
	 * @param int $userId User Id
	 *
	 * @return string
	 */
	private static function userKey($userId): string
	{
		return $customerId !== null ? ":u:{$userId}" : '';
	}
}
