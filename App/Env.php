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

    public static $allowConfigRequest = null;
    public static $configRequestRouteKeyword = null;

    public static $allowImportRequest = null;
    public static $importRequestRouteKeyword = null;
    public static $importSampleRouteKeyword = null;

    // Export CSV
    public static $allowExport = null;
    public static $mySqlBinaryLocationOnWebServer = null;

    // Global counter
    public static $useGlobalCounter = null;
    public static $gCounter = null;
    public static $gCounterMode = null;

    public static $clients = null;
    public static $groups = null;
    public static $clientUsers = null;

    public static $maxResultsPerPage = null;
    public static $defaultPerPage = null;

    public static $allowCronRequest = null;
    public static $cronRequestRoutePrefix = null;
    public static $cronRestrictedCidr = null;

    public static $allowRoutesRequest = null;
    public static $routesRequestRoute = null;

    public static $allowCustomRequest = null;
    public static $customRequestRoutePrefix = null;

    public static $allowUploadRequest = null;
    public static $uploadRequestRoutePrefix = null;

    public static $allowThirdPartyRequest = null;
    public static $thirdPartyRequestRoutePrefix = null;

    public static $allowCacheRequest = null;
    public static $cacheRequestRoutePrefix = null;

    public static $iRepresentation = null;
    public static $oRepresentation = null;

    public static $allowRepresentationAsQueryParam = null;

    public static $authMode = null;
    public static $sessionMode = null;

    private static $iAllowedRepresentation = ['JSON', 'XML'];
    private static $oAllowedRepresentation = ['JSON', 'XML', 'HTML'];

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

        self::$allowConfigRequest = (int)getenv(name: 'allowConfigRequest');
        self::$configRequestRouteKeyword = getenv(name: 'configRequestRouteKeyword');

        self::$allowImportRequest = (int)getenv(name: 'allowImportRequest');
        self::$importRequestRouteKeyword = getenv(name: 'importRequestRouteKeyword');
        self::$importSampleRouteKeyword = getenv(name: 'importSampleRouteKeyword');

        // Export CSV
        self::$allowExport = (int)getenv(name: 'allowExport');
        self::$mySqlBinaryLocationOnWebServer = getenv(name: 'mySqlBinaryLocationOnWebServer');

        // Global counter
        self::$useGlobalCounter = (int)getenv(name: 'useGlobalCounter');
        self::$gCounter = getenv(name: 'gCounter');
        self::$gCounterMode = getenv(name: 'gCounterMode');

        self::$clients = getenv(name: 'clients');
        self::$groups = getenv(name: 'groups');
        self::$clientUsers = getenv(name: 'clientUsers');

        self::$maxResultsPerPage = (int)getenv(name: 'maxResultsPerPage');
        self::$defaultPerPage = (int)getenv(name: 'defaultPerPage');

        self::$allowCronRequest = (int)getenv(name: 'allowCronRequest');
        self::$cronRequestRoutePrefix = getenv(name: 'cronRequestRoutePrefix');
        self::$cronRestrictedCidr = getenv(name: 'cronRestrictedCidr');

        self::$allowRoutesRequest = (int)getenv(name: 'allowRoutesRequest');
        self::$routesRequestRoute = getenv(name: 'routesRequestRoute');

        self::$allowCustomRequest = (int)getenv(name: 'allowCustomRequest');
        self::$customRequestRoutePrefix = getenv(name: 'customRequestRoutePrefix');

        self::$allowUploadRequest = (int)getenv(name: 'allowUploadRequest');
        self::$uploadRequestRoutePrefix = getenv(name: 'uploadRequestRoutePrefix');

        self::$allowThirdPartyRequest = (int)getenv(name: 'allowThirdPartyRequest');
        self::$thirdPartyRequestRoutePrefix = getenv(
            name: 'thirdPartyRequestRoutePrefix'
        );

        self::$allowCacheRequest = (int)getenv(name: 'allowCacheRequest');
        self::$cacheRequestRoutePrefix = getenv(name: 'cacheRequestRoutePrefix');

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

        self::$allowRepresentationAsQueryParam = (int)getenv(name: 'allowRepresentationAsQueryParam');

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
