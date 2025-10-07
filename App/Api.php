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

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Hook;

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
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Hook object
     *
     * @var null|Hook
     */
    private $hook = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->c->req->loadClientDetails();

        if (!$this->c->req->open) {
            $this->c->req->auth->loadUserDetails();
            $this->c->req->auth->loadGroupDetails();
        }

        $this->c->req->rParser->parseRoute();
        $this->c->req->setDatabaseCacheKey();

        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        // Execute Pre Route Hooks
        if (isset($this->c->req->rParser->routeHook['__PRE-ROUTE-HOOKS__'])) {
            if ($this->hook === null) {
                $this->hook = new Hook(common: $this->c);
            }
            $this->hook->triggerHook(
                hookConfig: $this->c->req->rParser->routeHook['__PRE-ROUTE-HOOKS__']
            );
        }

        try {
            if ($this->processBeforePayload()) {
                return true;
            }
        } catch (\Exception $e) {
            return true;
        }

        // Load Payloads
        if (!$this->c->req->rParser->isConfigRequest) {
            $this->c->req->loadPayload();
        }

        $class = null;
        switch ($this->c->req->METHOD) {
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
            $api = new $class(common: $this->c);
            if ($api->init()) {
                $api->process();
            }
        }

        // Check & Process Cron / ThirdParty calls
        $this->processAfterPayload();

        // Execute Post Route Hooks
        if (isset($this->c->req->rParser->routeHook['__POST-ROUTE-HOOKS__'])) {
            if ($this->hook === null) {
                $this->hook = new Hook(common: $this->c);
            }
            $this->hook->triggerHook(
                hookConfig: $this->c->req->rParser->routeHook['__POST-ROUTE-HOOKS__']
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
            && Env::$routesRequestUri === $this->c->req->rParser->routeElements[0]
        ) {
            $this->beforePayload = true;
            $supplementApiClass = __NAMESPACE__ . '\\Routes';
            $supplementObj = new $supplementApiClass(common: $this->c);
            if ($supplementObj->init()) {
                $supplementObj->process();
            }
            $foundClass = true;
        } else {
            $supplementApiClass = null;
            switch ($this->c->req->rParser->routeElements[0]) {
                case Env::$allowCustomRequest
                    && (Env::$customRequestUriPrefix
                        === $this->c->req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\Custom';
                    break;
                case Env::$allowUploadRequest
                    && (Env::$uploadRequestUriPrefix
                        === $this->c->req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\Upload';
                    break;
                case Env::$allowThirdPartyRequest
                    && (Env::$thirdPartyRequestUriPrefix
                        === $this->c->req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\ThirdParty';
                    break;
                case Env::$allowCacheRequest
                    && (Env::$cacheRequestUriPrefix
                        === $this->c->req->rParser->routeElements[0]):
                    $supplementApiClass = __NAMESPACE__ . '\\CacheHandler';
                    break;
            }

            if (!empty($supplementApiClass)) {
                $this->beforePayload = true;
                $supplementObj = new $supplementApiClass(common: $this->c);
                $supplementObj->init();
                $supplement = new Supplement(common: $this->c);
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
