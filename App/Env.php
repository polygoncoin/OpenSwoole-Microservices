<?php

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\HttpStatus;
use Microservices\App\SessionHandlers\Session;

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Env
{
    public static $timestamp = null;
///////////////

    public static $ENVIRONMENT = null;
    public static $OUTPUT_PERFORMANCE_STATS = null;
    public static $DISABLE_REQUESTS_VIA_PROXIES = null;

    public static $enableOpenRequests = null;
    public static $enableAuthRequests = null;
    public static $authMode = null;
    public static $sessionMode = null;
    public static $enableConcurrentLogins = null;
    public static $maxConcurrentLogins = null;
    public static $concurrentAccessInterval = null;

    public static $iRepresentation = null;
    public static $oRepresentation = null;
    public static $enableInputRepresentationAsQueryParam = null;
    public static $enableOutputRepresentationAsQueryParam = null;
    public static $enablePayloadInResponse = null;
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

    public static $masterDatabase = null;
    public static $clientsTable = null;
    public static $groupsTable = null;
    public static $queryPlaceholder = null;
    public static $defaultPerPage = null;
    public static $maxResultsPerPage = null;

    public static $enableGlobalCounter = null;
    public static $gCounter = null;
    public static $gCounterMode = null;

    public static $idempotentSecret = null;
///////////////

    public static $enableConfigRequest = null;
    public static $enableExportRequest = null;
    public static $enableImportRequest = null;
    public static $enableImportSampleRequest = null;
    public static $enableRoutesRequest = null;
    public static $enableResponseCaching = null;
    public static $enableDropboxRequest = null;
    public static $enableCronRequest = null;
    public static $enableCustomRequest = null;
    public static $enableReloadRequest = null;
    public static $enableThirdPartyRequest = null;
    public static $enableUploadRequest = null;

    public static $configRequestRouteKeyword = null;
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
///////////////

    public static $enableCidrChecks = null;

    public static $configRestrictedCidr = null;
    public static $exportRestrictedCidr = null;
    public static $importRestrictedCidr = null;
    public static $importSampleRestrictedCidr = null;
    public static $routesRestrictedCidr = null;
    public static $cacheRestrictedCidr = null;
    public static $cronRestrictedCidr = null;
    public static $customRestrictedCidr = null;
    public static $reloadRestrictedCidr = null;
    public static $thirdPatyRestrictedCidr = null;
    public static $uploadRestrictedCidr = null;
///////////////

    public static $enableRateLimiting = null;
    public static $enableRateLimitAtIpLevel = null;
    public static $enableRateLimitAtClientLevel = null;
    public static $enableRateLimitAtGroupLevel = null;
    public static $enableRateLimitAtUserLevel = null;
    public static $enableRateLimitAtRouteLevel = null;
    public static $enableRateLimitAtUsersPerIpLevel = null;
    public static $enableRateLimitAtUsersRequestLevel = null;

    public static $rateLimitServerType = null;
    public static $rateLimitServerHostname = null;
    public static $rateLimitServerPort = null;

    public static $rateLimitIPPrefix = null;
    public static $rateLimitClientPrefix = null;
    public static $rateLimitGroupPrefix = null;
    public static $rateLimitUserPrefix = null;
    public static $rateLimitRoutePrefix = null;
    public static $rateLimitUsersPerIpPrefix = null;
    public static $rateLimitUserLoginPrefix = null;
    public static $rateLimitUsersRequestPrefix = null;

    public static $rateLimitIPMaxRequests = null;
    public static $rateLimitIPMaxRequestsWindow = null;

    public static $rateLimitUsersPerIpMaxUsers = null;
    public static $rateLimitUsersPerIpMaxUsersWindow = null;

    public static $rateLimitUsersMaxRequests = null;
    public static $rateLimitUsersMaxRequestsWindow = null;

    public static $rateLimitMaxUserLoginRequests = null;
    public static $rateLimitMaxUserLoginRequestsWindow = null;
///////////////

    public static $sqlResultsCacheServerType = null;
    public static $sqlResultsCacheServerHostname = null;
    public static $sqlResultsCacheServerPort = null;
    public static $sqlResultsCacheServerUsername = null;
    public static $sqlResultsCacheServerPassword = null;
    public static $sqlResultsCacheServerDatabase = null;
    public static $sqlResultsCacheServerTable = null;
///////////////

    public static $reservedRoutesPrefix = null;
    public static $reservedRoutesCidrString = null;

    public static $iAllowedRepresentation = ['JSON', 'XML'];
    public static $oAllowedRepresentation = ['JSON', 'XML', 'XSLT', 'HTML', 'PHP'];

    /**
     * Initialize
     *
     * @return void
     */
    public static function init(): void
    {
        self::$ENVIRONMENT = getenv(name: 'ENVIRONMENT');
        self::$OUTPUT_PERFORMANCE_STATS = getenv(name: 'OUTPUT_PERFORMANCE_STATS');
        self::$DISABLE_REQUESTS_VIA_PROXIES = getenv(name: 'DISABLE_REQUESTS_VIA_PROXIES');

        self::$enableOpenRequests = (bool)((int)getenv(name: 'enableOpenRequests'));
        self::$enableAuthRequests = (bool)((int)getenv(name: 'enableAuthRequests'));
        self::$authMode = getenv(name: 'authMode');
        self::$sessionMode = getenv(name: 'sessionMode');
        self::$enableConcurrentLogins = getenv(name: 'DISABLenableConcurrentLoginsE_REQUESTS_VIA_PROXIES');
        self::$maxConcurrentLogins = getenv(name: 'maxConcurrentLogins');
        self::$concurrentAccessInterval = getenv(name: 'concurrentAccessInterval');

        self::$iRepresentation = getenv(name: 'iRepresentation');
        self::$oRepresentation = getenv(name: 'oRepresentation');
        self::$enableInputRepresentationAsQueryParam = (bool)((int)getenv(name: 'enableInputRepresentationAsQueryParam'));
        self::$enableOutputRepresentationAsQueryParam = (bool)((int)getenv(name: 'enableOutputRepresentationAsQueryParam'));
        self::$enablePayloadInResponse = (bool)((int)getenv(name: 'enablePayloadInResponse'));
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

        self::$masterDatabase = getenv(name: 'masterDatabase');
        self::$clientsTable = getenv(name: 'clientsTable');
        self::$groupsTable = getenv(name: 'groupsTable');
        self::$queryPlaceholder = getenv(name: 'queryPlaceholder');
        self::$defaultPerPage = (int)getenv(name: 'defaultPerPage');
        self::$maxResultsPerPage = (int)getenv(name: 'maxResultsPerPage');

        self::$enableGlobalCounter = (bool)((int)getenv(name: 'enableGlobalCounter'));
        self::$gCounter = getenv(name: 'gCounter');
        self::$gCounterMode = getenv(name: 'gCounterMode');

        self::$idempotentSecret = getenv(name: 'idempotentSecret');
        //////////////////

        self::$enableConfigRequest = (bool)((int)getenv(name: 'enableConfigRequest'));
        self::$enableExportRequest = (bool)((int)getenv(name: 'enableExportRequest'));
        self::$enableImportRequest = (bool)((int)getenv(name: 'enableenableImportRequestGlobal'));
        self::$enableImportSampleRequest = (bool)((int)getenv(name: 'enableImportSampleRequest'));
        self::$enableRoutesRequest = (bool)((int)getenv(name: 'enableRoutesRequest'));
        self::$enableResponseCaching = (bool)((int)getenv(name: 'enableResponseCaching'));
        self::$enableDropboxRequest = (bool)((int)getenv(name: 'enableDropboxRequest'));
        self::$enableCronRequest = (bool)((int)getenv(name: 'enableCronRequest'));
        self::$enableCustomRequest = (bool)((int)getenv(name: 'enableCustomRequest'));
        self::$enableReloadRequest = (bool)((int)getenv(name: 'enableReloadRequest'));
        self::$enableThirdPartyRequest = (bool)((int)getenv(name: 'enableThirdPartyRequest'));
        self::$enableUploadRequest = (bool)((int)getenv(name: 'enableUploadRequest'));

        self::$configRequestRouteKeyword = getenv(name: 'configRequestRouteKeyword');
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
        //////////////////

        self::$enableCidrChecks = (bool)((int)getenv(name: 'enableCidrChecks'));

        self::$configRestrictedCidr = getenv(name: 'authconfigRestrictedCidrMode');
        self::$exportRestrictedCidr = getenv(name: 'exportRestrictedCidr');
        self::$importRestrictedCidr = getenv(name: 'importRestrictedCidr');
        self::$importSampleRestrictedCidr = getenv(name: 'importSampleRestrictedCidr');
        self::$routesRestrictedCidr = getenv(name: 'routesRestrictedCidr');
        self::$cacheRestrictedCidr = getenv(name: 'cacheRestrictedCidr');
        self::$cronRestrictedCidr = getenv(name: 'autcronRestrictedCidrhMode');
        self::$customRestrictedCidr = getenv(name: 'customRestrictedCidr');
        self::$reloadRestrictedCidr = getenv(name: 'reloadRestrictedCidr');
        self::$thirdPatyRestrictedCidr = getenv(name: 'thirdPatyRestrictedCidr');
        self::$uploadRestrictedCidr = getenv(name: 'uploadRestrictedCidr');
        //////////////////

        self::$enableRateLimiting = (bool)((int)getenv(name: 'enableRateLimiting'));
        self::$enableRateLimitAtIpLevel = (bool)((int)getenv(name: 'enableRateLimitAtIpLevel'));
        self::$enableRateLimitAtClientLevel = (bool)((int)getenv(name: 'eenableRateLimitAtClientLevelnableUploadRequest'));
        self::$enableRateLimitAtGroupLevel = (bool)((int)getenv(name: 'enableRateLimitAtGroupLevel'));
        self::$enableRateLimitAtUserLevel = (bool)((int)getenv(name: 'enableRateLimitAtUserLevel'));
        self::$enableRateLimitAtRouteLevel = (bool)((int)getenv(name: 'enableRateLimitAtRouteLevel'));
        self::$enableRateLimitAtUsersPerIpLevel = (bool)((int)getenv(name: 'enableRateLimitAtUsersPerIpLevel'));
        self::$enableRateLimitAtUsersRequestLevel = (bool)((int)getenv(name: 'enableRateLimitAtUsersRequestLevel'));

        self::$rateLimitServerType = getenv(name: 'rateLimitServerType');
        self::$rateLimitServerHostname = getenv(name: 'rateLimitServerHostname');
        self::$rateLimitServerPort = (int)getenv(name: 'rateLimitServerPort');

        self::$rateLimitIPPrefix = getenv(name: 'rateLimitIPPrefix');
        self::$rateLimitClientPrefix = getenv(name: 'rateLimitClientPrefix');
        self::$rateLimitGroupPrefix = getenv(name: 'rateLimitGroupPrefix');
        self::$rateLimitUserPrefix = getenv(name: 'rateLimitUserPrefix');
        self::$rateLimitRoutePrefix = getenv(name: 'rateLimitRoutePrefix');
        self::$rateLimitUsersPerIpPrefix = getenv(name: 'rateLimitUsersPerIpPrefix');
        self::$rateLimitUserLoginPrefix = getenv(name: 'rateLimitUserLoginPrefix');
        self::$rateLimitUsersRequestPrefix = getenv(name: 'rateLimitUsersRequestPrefix');

        self::$rateLimitIPMaxRequests = (int)getenv(name: 'rateLimitIPMaxRequests');
        self::$rateLimitIPMaxRequestsWindow = (int)getenv(name: 'rateLimitIPMaxRequestsWindow');

        self::$rateLimitUsersPerIpMaxUsers = (int)getenv(name: 'rateLimitUsersPerIpMaxUsers');
        self::$rateLimitUsersPerIpMaxUsersWindow = (int)getenv(name: 'rateLimitUsersPerIpMaxUsersWindow');

        self::$rateLimitUsersMaxRequests = (int)getenv(name: 'rateLimitUsersMaxRequests');
        self::$rateLimitUsersMaxRequestsWindow = (int)getenv(name: 'rateLimitUsersMaxRequestsWindow');

        self::$rateLimitMaxUserLoginRequests = (int)getenv(name: 'rateLimitMaxUserLoginRequests');
        self::$rateLimitMaxUserLoginRequestsWindow = (int)getenv(name: 'rateLimitMaxUserLoginRequestsWindow');
        //////////////////

        self::$sqlResultsCacheServerType = getenv(name: 'sqlResultsCacheServerType');
        self::$sqlResultsCacheServerHostname = getenv(name: 'sqlResultsCacheServerHostname');
        self::$sqlResultsCacheServerPort = getenv(name: 'sqlResultsCacheServerPort');
        self::$sqlResultsCacheServerUsername = getenv(name: 'sqlResultsCacheServerUsername');
        self::$sqlResultsCacheServerPassword = getenv(name: 'sqlResultsCacheServerPassword');
        self::$sqlResultsCacheServerDatabase = getenv(name: 'sqlResultsCacheServerDatabase');
        self::$sqlResultsCacheServerTable = getenv(name: 'sqlResultsCacheServerTable');
        //////////////////

        self::$reservedRoutesPrefix = [
            self::$routesRequestRoute,
            self::$dropboxRequestRoutePrefix,
            self::$cronRequestRoutePrefix,
            self::$customRequestRoutePrefix,
            self::$reloadRequestRoutePrefix,
            self::$thirdPartyRequestRoutePrefix,
            self::$uploadRequestRoutePrefix
        ];

        self::$reservedRoutesCidrString = [
            self::$routesRequestRoute => self::$routesRestrictedCidr,
            self::$dropboxRequestRoutePrefix => self::$cacheRestrictedCidr,
            self::$cronRequestRoutePrefix => self::$cronRestrictedCidr,
            self::$customRequestRoutePrefix => self::$customRestrictedCidr,
            self::$reloadRequestRoutePrefix => self::$reloadRestrictedCidr,
            self::$thirdPartyRequestRoutePrefix => self::$thirdPatyRestrictedCidr,
            self::$uploadRequestRoutePrefix => self::$uploadRestrictedCidr
        ];

        if (self::$authMode === 'Session') {
            // Initialize Session Handler
            Session::initSessionHandler(sessionMode: Env::$sessionMode, options: []);

            // Start session in readonly mode
            Session::sessionStartReadonly();
        }
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
