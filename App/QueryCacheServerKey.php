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
	 * Open To Web App key
	 *
	 * @var string
	 */
	public static $oApp = 'o:app';

	/**
	 * Auth Based App key
	 *
	 * @var string
	 */
	public static $aApp = 'a:app';

	/**
	 * Customer Key
	 *
	 * @param int  $customerID         Customer ID
	 * @param bool $isOpenToWebRequest
	 *
	 * @return string
	 */
	public static function customer(
		$customerID,
		$isOpenToWebRequest = false
	): string
	{
		$appKey = self::appKey($isOpenToWebRequest);
		$customerKey = self::customerKey($customerID);

		return $appKey . $customerKey;
	}

	/**
	 * User Group Key
	 *
	 * @param int  $customerID         Customer ID
	 * @param int  $groupID            Group ID
	 * @param bool $isOpenToWebRequest
	 *
	 * @return string
	 */
	public static function group(
		$customerID = null,
		$groupID = null,
		$isOpenToWebRequest = false
	): string
	{
		$appKey = self::appKey($isOpenToWebRequest);
		$customerKey = self::customerKey($customerID);
		$groupKey = self::groupKey($groupID);

		return $appKey . $customerKey . $groupKey;
	}

	/**
	 * Customer Group User Key
	 *
	 * @param int  $customerID         Customer ID
	 * @param int  $groupID            Group ID
	 * @param int  $userID             User ID
	 * @param bool $isOpenToWebRequest
	 *
	 * @return string
	 */
	public static function user(
		$customerID = null,
		$groupID = null,
		$userID = null,
		$isOpenToWebRequest = false
	): string
	{
		$appKey = self::appKey($isOpenToWebRequest);
		$customerKey = self::customerKey($customerID);
		$groupKey = self::groupKey($groupID);
		$userKey = self::userKey($userID);

		return $appKey . $customerKey . $groupKey . $userKey;
	}

	/**
	 * Category
	 *
	 * @param int  $customerID         Customer ID
	 * @param int  $groupID            Group ID
	 * @param int  $userID             User ID
	 * @param bool $isOpenToWebRequest
	 *
	 * @return string
	 */
	public static function category(
		$customerID = null,
		$groupID = null,
		$isOpenToWebRequest = false
	): string
	{
		$appKey = self::appKey($isOpenToWebRequest);
		$customerKey = self::customerKey($customerID);
		$groupKey = self::groupKey($groupID);

		return $appKey . $customerKey . $groupKey . ':category';
	}

	/**
	 * Category1
	 *
	 * @param int  $customerID         Customer ID
	 * @param int  $groupID            Group ID
	 * @param bool $isOpenToWebRequest
	 *
	 * @return string
	 */
	public static function category1(
		$customerID = null,
		$groupID = null,
		$isOpenToWebRequest = false
	): string
	{
		$appKey = self::appKey($isOpenToWebRequest);
		$customerKey = self::customerKey($customerID);
		$groupKey = self::groupKey($groupID);

		return $appKey . $customerKey . $groupKey . ':category:1';
	}

	/**
	 * App Key Details
	 *
	 * @param bool $isOpenToWebRequest
	 *
	 * @return string
	 */
	private static function appKey($isOpenToWebRequest = false): string
	{
		return $isOpenToWebRequest ? self::$oApp : self::$aApp;
	}

	/**
	 * Customer Key Details
	 *
	 * @param int $customerID Customer ID
	 *
	 * @return string
	 */
	private static function customerKey($customerID): string
	{
		return $customerID !== null ? ":c:{$customerID}" : '';
	}

	/**
	 * Group Key Details
	 *
	 * @param int $groupID Group ID
	 *
	 * @return string
	 */
	private static function groupKey($groupID): string
	{
		return $groupID !== null ? ":g:{$groupID}" : '';
	}

	/**
	 * User Key Details
	 *
	 * @param int $userID User ID
	 *
	 * @return string
	 */
	private static function userKey($userID): string
	{
		return $userID !== null ? ":u:{$userID}" : '';
	}
}
