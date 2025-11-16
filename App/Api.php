<?php

/**
 * Initiating API
 * php version 8.3
 *
 * @category  API
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheHandler;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\Supplement;

/**
 * Class to initialize api HTTP request
 * php version 8.3
 *
 * @category  API
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Api
{
    /**
     * Route matched for processing before payload was collected
     *
     * @var null|bool
     */
    private $beforePayload = null;

    /**
     * Hook object
     *
     * @var null|Hook
     */
    private $hook = null;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        Common::$req->loadClientDetails();

        if (!Common::$req->open) {
            Common::$req->auth->loadUserDetails();
            Common::$req->auth->loadGroupDetails();
        }

        Common::$req->rParser->parseRoute();
        DbFunctions::setDatabaseCacheKey();

        return true;
    }

    /**
     * Process
     *
     * @return mixed
     */
    public function process(): mixed
    {
        if (Common::$req->METHOD === Constants::$GET) {
            $cacheHandler = new CacheHandler(http: Common::$http);
            if ($cacheHandler->init(mode: 'Closed')) {
                // File exists - Serve from Dropbox
                return $cacheHandler->process();
            }
            $cacheHandler = null;
        }

        // Execute Pre Route Hooks
        if (isset(Common::$req->rParser->routeHook['__PRE-ROUTE-HOOKS__'])) {
            if ($this->hook === null) {
                $this->hook = new Hook();
            }
            $this->hook->triggerHook(
                hookConfig: Common::$req->rParser->routeHook['__PRE-ROUTE-HOOKS__']
            );
        }

        // Load Payloads
        if (!Common::$req->rParser->isConfigRequest) {
            Common::$req->loadPayload();
        }

        if ($this->processBeforePayload()) {
            return true;
        }

        $class = null;
        switch (Common::$req->METHOD) {
            case Constants::$GET:
                $class = __NAMESPACE__ . '\\Read';
                break;
            case Constants::$POST:
            case Constants::$PUT:
            case Constants::$PATCH:
            case Constants::$DELETE:
                $class = __NAMESPACE__ . '\\Write';
                break;
        }

        if ($class !== null) {
            $api = new $class();
            if ($api->init()) {
                $return = $api->process();
                if (
                    is_array($return)
                    && count($return) === 3
                ) {
                    return $return;
                }
            }
        }

        // Check & Process Cron / ThirdParty calls
        $this->processAfterPayload();

        // Execute Post Route Hooks
        if (isset(Common::$req->rParser->routeHook['__POST-ROUTE-HOOKS__'])) {
            if ($this->hook === null) {
                $this->hook = new Hook();
            }
            $this->hook->triggerHook(
                hookConfig: Common::$req->rParser->routeHook['__POST-ROUTE-HOOKS__']
            );
        }

        return true;
    }

    /**
     * Miscellaneous Functionality Before Collecting Payload
     *
     * @return bool
     */
    private function processBeforePayload(): bool
    {
        $foundClass = false;

        if (
            Env::$allowRoutesRequest
            && Env::$routesRequestUri === Common::$req->rParser->routeElements[0]
        ) {
            $this->beforePayload = true;
            $supplementApiClass = __NAMESPACE__ . '\\Routes';
            $supplementObj = new $supplementApiClass();
            if ($supplementObj->init()) {
                $supplementObj->process();
            }
            $foundClass = true;
        } else {
            $supplementApiClass = null;
            switch (Common::$req->rParser->routeElements[0]) {
                case Env::$allowCustomRequest
                    && (Env::$customRequestUriPrefix
                        === Common::$req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\Custom';
                    break;
                case Env::$allowUploadRequest
                    && (Env::$uploadRequestUriPrefix
                        === Common::$req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\Upload';
                    break;
                case Env::$allowThirdPartyRequest
                    && (Env::$thirdPartyRequestUriPrefix
                        === Common::$req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\ThirdParty';
                    break;
                case Env::$allowCacheRequest
                    && (Env::$cacheRequestUriPrefix
                        === Common::$req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\CacheHandler';
                    break;
            }

            if (!empty($supplementApiClass)) {
                $this->beforePayload = true;
                $supplementObj = new $supplementApiClass();
                $supplementObj->init();
                $supplement = new Supplement();
                if ($supplement->init(supplementObj: $supplementObj)) {
                    $supplement->process();
                }
                $foundClass = true;
            }
        }

        return $foundClass;
    }

    /**
     * Miscellaneous Functionality After Collecting Payload
     *
     * @return bool
     */
    private function processAfterPayload(): bool
    {
        return true;
    }
}
