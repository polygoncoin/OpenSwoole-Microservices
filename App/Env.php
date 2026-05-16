<?php

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\HttpStatus;

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Env
{
	public static $timestamp = null;

	public static $ENVIRONMENT = null;
	public static $OUTPUT_PERFORMANCE_STATS = null;
	public static $DISABLE_REQUESTS_VIA_PROXIES = null;

	public static $authMode = null;
	public static $sessionMode = null;
	public static $maxConcurrentLogin = null;
	public static $concurrentAccessInterval = null;

	public static $iRepresentation = null;
	public static $oRepresentation = null;
	public static $payloadKeyInResponse = null;

	public static $gCacheServerType = null;
	public static $gCacheServerHostname = null;
	public static $gCacheServerPort = null;
	public static $gCacheServerUsername = null;
	public static $gCacheServerPassword = null;
	public static $gCacheServerDatabase = null;
	public static $gCacheServerTable = null;

	public static $gDbServerType = null;
	public static $gDbServerHostname = null;
	public static $gDbServerPort = null;
	public static $gDbServerUsername = null;
	public static $gDbServerPassword = null;
	public static $gDbServerDatabase = null;
	public static $gDbServerQueryPlaceholder = null;

	public static $customerMasterDb = null;
	public static $customerTable = null;
	public static $groupTable = null;
	public static $queryPlaceholder = null;
	public static $defaultPerPage = null;
	public static $maxResultsPerPage = null;

	public static $gCounter = null;
	public static $gCounterMode = null;

	public static $idempotentSecret = null;

	public static $explainRequestRouteKeyword = null;
	public static $mySqlBinaryLocationOnWebServer = null;
	public static $importRequestRouteKeyword = null;
	public static $importSampleRequestRouteKeyword = null;
	public static $routesRequestRoute = null;
	public static $dropboxRequestRoutePrefix = null;
	public static $cronRequestRoutePrefix = null;
	public static $customRequestRoutePrefix = null;
	public static $reloadRequestRoutePrefix = null;
	public static $thirdPartyRequestRoutePrefix = null;
	public static $uploadRequestRoutePrefix = null;

	public static $explainRestrictedCidr = null;
	public static $exportRestrictedCidr = null;
	public static $importRestrictedCidr = null;
	public static $importSampleRestrictedCidr = null;
	public static $routesRestrictedCidr = null;
	public static $dropboxRestrictedCidr = null;
	public static $cronRestrictedCidr = null;
	public static $customRestrictedCidr = null;
	public static $reloadRestrictedCidr = null;
	public static $thirdPatyRestrictedCidr = null;
	public static $uploadRestrictedCidr = null;

	public static $rateLimitIPPrefix = null;
	public static $rateLimitCustomerPrefix = null;
	public static $rateLimitGroupPrefix = null;
	public static $rateLimitUserPrefix = null;
	public static $rateLimitRoutePrefix = null;
	public static $rateLimitUserPerIpPrefix = null;
	public static $rateLimitUserLoginPrefix = null;
	public static $rateLimitUserRequestPrefix = null;

	public static $queryCacheServerType = null;
	public static $queryCacheServerHostname = null;
	public static $queryCacheServerPort = null;
	public static $queryCacheServerUsername = null;
	public static $queryCacheServerPassword = null;
	public static $queryCacheServerDatabase = null;
	public static $queryCacheServerTable = null;

	public static $iAllowedRepresentation = ['JSON', 'XML'];
	public static $oAllowedRepresentation = ['JSON', 'XML', 'XSLT', 'HTML', 'PHP'];

	public static $isInitiated = false;

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function init(): void
	{
		if (self::$isInitiated) {
			return;
		}

		self::$isInitiated = true;

		self::$ENVIRONMENT = getenv(name: 'ENVIRONMENT');
		self::$OUTPUT_PERFORMANCE_STATS = getenv(name: 'OUTPUT_PERFORMANCE_STATS');
		self::$DISABLE_REQUESTS_VIA_PROXIES = getenv(name: 'DISABLE_REQUESTS_VIA_PROXIES');

		self::$authMode = getenv(name: 'authMode');
		self::$sessionMode = getenv(name: 'sessionMode');
		self::$maxConcurrentLogin = getenv(name: 'maxConcurrentLogin');
		self::$concurrentAccessInterval = getenv(name: 'concurrentAccessInterval');

		self::$iRepresentation = getenv(name: 'iRepresentation');
		self::$oRepresentation = getenv(name: 'oRepresentation');
		self::$payloadKeyInResponse = getenv(name: 'payloadKeyInResponse');

		self::$gCacheServerType = getenv(name: 'gCacheServerType');
		self::$gCacheServerHostname = getenv(name: 'gCacheServerHostname');
		self::$gCacheServerPort = (int)getenv(name: 'gCacheServerPort');
		self::$gCacheServerUsername = getenv(name: 'gCacheServerUsername');
		self::$gCacheServerPassword = getenv(name: 'gCacheServerPassword');
		self::$gCacheServerDatabase = getenv(name: 'gCacheServerDatabase');
		self::$gCacheServerTable = getenv(name: 'gCacheServerTable');

		self::$gDbServerType = getenv(name: 'gDbServerType');
		self::$gDbServerHostname = getenv(name: 'gDbServerHostname');
		self::$gDbServerPort = (int)getenv(name: 'gDbServerPort');
		self::$gDbServerUsername = getenv(name: 'gDbServerUsername');
		self::$gDbServerPassword = getenv(name: 'gDbServerPassword');
		self::$gDbServerDatabase = getenv(name: 'gDbServerDatabase');
		self::$gDbServerQueryPlaceholder = getenv(name: 'gDbServerQueryPlaceholder');

		self::$customerMasterDb = getenv(name: 'customerMasterDb');
		self::$customerTable = getenv(name: 'customerTable');
		self::$groupTable = getenv(name: 'groupTable');
		self::$queryPlaceholder = getenv(name: 'queryPlaceholder');
		self::$defaultPerPage = (int)getenv(name: 'defaultPerPage');
		self::$maxResultsPerPage = (int)getenv(name: 'maxResultsPerPage');

		self::$gCounter = getenv(name: 'gCounter');
		self::$gCounterMode = getenv(name: 'gCounterMode');

		self::$idempotentSecret = getenv(name: 'idempotentSecret');

		self::$explainRequestRouteKeyword = getenv(name: 'explainRequestRouteKeyword');
		self::$mySqlBinaryLocationOnWebServer = getenv(name: 'mySqlBinaryLocationOnWebServer');
		self::$importRequestRouteKeyword = getenv(name: 'importRequestRouteKeyword');
		self::$importSampleRequestRouteKeyword = getenv(name: 'importSampleRequestRouteKeyword');
		self::$routesRequestRoute = getenv(name: 'routesRequestRoute');
		self::$dropboxRequestRoutePrefix = getenv(name: 'dropboxRequestRoutePrefix');
		self::$cronRequestRoutePrefix = getenv(name: 'cronRequestRoutePrefix');
		self::$customRequestRoutePrefix = getenv(name: 'customRequestRoutePrefix');
		self::$reloadRequestRoutePrefix = getenv(name: 'reloadRequestRoutePrefix');
		self::$thirdPartyRequestRoutePrefix = getenv(name: 'thirdPartyRequestRoutePrefix');
		self::$uploadRequestRoutePrefix = getenv(name: 'uploadRequestRoutePrefix');

		self::$rateLimitIPPrefix = getenv(name: 'rateLimitIPPrefix');
		self::$rateLimitCustomerPrefix = getenv(name: 'rateLimitCustomerPrefix');
		self::$rateLimitGroupPrefix = getenv(name: 'rateLimitGroupPrefix');
		self::$rateLimitUserPrefix = getenv(name: 'rateLimitUserPrefix');
		self::$rateLimitRoutePrefix = getenv(name: 'rateLimitRoutePrefix');
		self::$rateLimitUserPerIpPrefix = getenv(name: 'rateLimitUserPerIpPrefix');
		self::$rateLimitUserLoginPrefix = getenv(name: 'rateLimitUserLoginPrefix');
		self::$rateLimitUserRequestPrefix = getenv(name: 'rateLimitUserRequestPrefix');

		self::$queryCacheServerType = getenv(name: 'queryCacheServerType');
		self::$queryCacheServerHostname = getenv(name: 'queryCacheServerHostname');
		self::$queryCacheServerPort = getenv(name: 'queryCacheServerPort');
		self::$queryCacheServerUsername = getenv(name: 'queryCacheServerUsername');
		self::$queryCacheServerPassword = getenv(name: 'queryCacheServerPassword');
		self::$queryCacheServerDatabase = getenv(name: 'queryCacheServerDatabase');
		self::$queryCacheServerTable = getenv(name: 'queryCacheServerTable');
	}

	/**
	 * Validate Data Representation
	 *
	 * @param string $dataRepresentation Data Representation
	 * @param string $mode               input / output
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function isValidDataRep($dataRepresentation, $mode): bool
	{
		switch ($mode) {
			case 'input':
				if (
					in_array(
						needle: $dataRepresentation,
						haystack: self::$iAllowedRepresentation
					)
				) {
					return true;
				} else {
					throw new \Exception(
						message: "Invalid Data Representation '{$dataRepresentation}'",
						code: HttpStatus::$InternalServerError
					);
				}
				break;
			case 'output':
				if (
					in_array(
						needle: $dataRepresentation,
						haystack: self::$oAllowedRepresentation
					)
				) {
					return true;
				} else {
					throw new \Exception(
						message: "Invalid Data Representation '{$dataRepresentation}'",
						code: HttpStatus::$InternalServerError
					);
				}
				break;
		}
		return false;
	}
}
