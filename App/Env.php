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

    public static $gDbServerDatabase = null;
    public static $cacheDatabase = null;

    public static $queryPlaceholder = null;

    public static $ENVIRONMENT = null;
    public static $OUTPUT_PERFORMANCE_STATS = null;

    public static $enableConfigRequest = null;
    public static $configRequestRouteKeyword = null;

    public static $enableImportRequest = null;
    public static $importRequestRouteKeyword = null;

    public static $enableImportSampleRequest = null;
    public static $importSampleRequestRouteKeyword = null;

    public static $enableExportRequest = null;
    public static $mySqlBinaryLocationOnWebServer = null;

    public static $enableCronRequest = null;
    public static $cronRequestRoutePrefix = null;

    public static $enableReloadRequest = null;
    public static $reloadRequestRoutePrefix = null;

    public static $enableRoutesRequest = null;
    public static $routesRequestRoute = null;

    public static $enableCustomRequest = null;
    public static $customRequestRoutePrefix = null;

    public static $enableUploadRequest = null;
    public static $uploadRequestRoutePrefix = null;

    public static $enableThirdPartyRequest = null;
    public static $thirdPartyRequestRoutePrefix = null;

    public static $enableDropboxRequest = null;
    public static $dropboxRequestRoutePrefix = null;

    public static $enableResponseCaching = null;

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

    public static $reservedRoutesPrefix = null;
    public static $reservedRoutesCidrString = null;

    // Global counter
    public static $enableGlobalCounter = null;
    public static $gCounter = null;
    public static $gCounterMode = null;

    public static $clientsTable = null;
    public static $groupsTable = null;

    public static $maxResultsPerPage = null;
    public static $defaultPerPage = null;

    public static $iRepresentation = null;
    public static $oRepresentation = null;
    public static $enableRepresentationAsQueryParam = null;
    public static $enablePayloadInResponse = null;
    public static $payloadKeyInResponse = null;

    public static $authMode = null;
    public static $sessionMode = null;

    private static $iAllowedRepresentation = ['JSON', 'XML'];
    private static $oAllowedRepresentation = ['JSON', 'XML', 'XSLT', 'HTML', 'PHP'];

    /**
     * Initialize
     *
     * @param array $http HTTP request details
     *
     * @return void
     */
    public static function init(&$http): void
    {
        self::$gDbServerDatabase = getenv(name: 'gDbServerDatabase');
        self::$cacheDatabase = getenv(name: 'gCacheServerDatabase');

        self::$queryPlaceholder = getenv(name: 'queryPlaceholder');

        self::$ENVIRONMENT = getenv(name: 'ENVIRONMENT');
        self::$OUTPUT_PERFORMANCE_STATS = getenv(name: 'OUTPUT_PERFORMANCE_STATS');

        self::$enableConfigRequest = (int)getenv(name: 'enableConfigRequest');
        self::$configRequestRouteKeyword = getenv(name: 'configRequestRouteKeyword');

        self::$enableImportRequest = (int)getenv(name: 'enableImportRequest');
        self::$importRequestRouteKeyword = getenv(name: 'importRequestRouteKeyword');

        self::$enableImportSampleRequest = (int)getenv(name: 'enableImportRequest');
        self::$importSampleRequestRouteKeyword = getenv(name: 'importSampleRequestRouteKeyword');

        self::$enableExportRequest = (int)getenv(name: 'enableExportRequest');
        self::$mySqlBinaryLocationOnWebServer = getenv(name: 'mySqlBinaryLocationOnWebServer');

        self::$enableCronRequest = (int)getenv(name: 'enableCronRequest');
        self::$cronRequestRoutePrefix = getenv(name: 'cronRequestRoutePrefix');

        self::$enableReloadRequest = (int)getenv(name: 'enableReloadRequest');
        self::$reloadRequestRoutePrefix = getenv(name: 'reloadRequestRoutePrefix');

        self::$enableRoutesRequest = (int)getenv(name: 'enableRoutesRequest');
        self::$routesRequestRoute = getenv(name: 'routesRequestRoute');

        self::$enableCustomRequest = (int)getenv(name: 'enableCustomRequest');
        self::$customRequestRoutePrefix = getenv(name: 'customRequestRoutePrefix');

        self::$enableUploadRequest = (int)getenv(name: 'enableUploadRequest');
        self::$uploadRequestRoutePrefix = getenv(name: 'uploadRequestRoutePrefix');

        self::$enableThirdPartyRequest = (int)getenv(name: 'enableThirdPartyRequest');
        self::$thirdPartyRequestRoutePrefix = getenv(
            name: 'thirdPartyRequestRoutePrefix'
        );

        self::$enableDropboxRequest = (int)getenv(name: 'enableDropboxRequest');
        self::$dropboxRequestRoutePrefix = getenv(name: 'dropboxRequestRoutePrefix');

        self::$enableResponseCaching = getenv(name: 'enableResponseCaching');

        self::$enableCidrChecks = (int)getenv(name: 'enableCidrChecks');
        self::$configRestrictedCidr = getenv(name: 'configRestrictedCidr');
        self::$exportRestrictedCidr = getenv(name: 'exportRestrictedCidr');
        self::$importRestrictedCidr = getenv(name: 'importRestrictedCidr');
        self::$importSampleRestrictedCidr = getenv(name: 'importSampleRestrictedCidr');
        self::$routesRestrictedCidr = getenv(name: 'routesRestrictedCidr');
        self::$cacheRestrictedCidr = getenv(name: 'cacheRestrictedCidr');
        self::$cronRestrictedCidr = getenv(name: 'cronRestrictedCidr');
        self::$customRestrictedCidr = getenv(name: 'customRestrictedCidr');
        self::$reloadRestrictedCidr = getenv(name: 'reloadRestrictedCidr');
        self::$thirdPatyRestrictedCidr = getenv(name: 'thirdPatyRestrictedCidr');
        self::$uploadRestrictedCidr = getenv(name: 'uploadRestrictedCidr');

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

        // Global counter
        self::$enableGlobalCounter = (int)getenv(name: 'enableGlobalCounter');
        self::$gCounter = getenv(name: 'gCounter');
        self::$gCounterMode = getenv(name: 'gCounterMode');

        self::$clientsTable = getenv(name: 'clientsTable');
        self::$groupsTable = getenv(name: 'groupsTable');

        self::$maxResultsPerPage = (int)getenv(name: 'maxResultsPerPage');
        self::$defaultPerPage = (int)getenv(name: 'defaultPerPage');

        $iRepresentation = getenv(name: 'iRepresentation');
        if (
            $iRepresentation !== false
            && self::isValidDataRep(dataRepresentation: $iRepresentation, mode: 'input')
        ) {
            self::$iRepresentation = getenv(name: 'iRepresentation');
        }
        $oRepresentation = getenv(name: 'oRepresentation');
        if (
            $oRepresentation !== false
            && self::isValidDataRep(dataRepresentation: $oRepresentation, mode: 'output')
        ) {
            self::$oRepresentation = getenv(name: 'oRepresentation');
        }
        self::$enableRepresentationAsQueryParam = (int)getenv(name: 'enableRepresentationAsQueryParam');
        self::$enablePayloadInResponse = (int)getenv(name: 'enablePayloadInResponse');
        self::$payloadKeyInResponse = getenv(name: 'payloadKeyInResponse');

        self::$sessionMode = getenv(name: 'sessionMode');

        self::$authMode = getenv(name: 'authMode');
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
