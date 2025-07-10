<?php
/**
 * Initiating API
 * php version 8.3
 *
 * @category  API
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
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
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Api
{
    /**
     * Route matched for processing before payload was collected
     *
     * @var null|bool
     */
    private $_beforePayload = null;

    /**
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Hook Object
     *
     * @var null|Hook
     */
    private $_hook = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->_c->req->loadClientDetails();

        if (!$this->_c->req->open) {
            $this->_c->req->auth->loadUserDetails();
            $this->_c->req->auth->loadGroupDetails();
        }

        $this->_c->req->parseRoute();
        $this->_c->req->setDatabaseCacheKey();

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
        if (isset($this->_c->req->routeHook['__PRE-ROUTE-HOOKS__'])) {
            if (is_null(value: $this->_hook)) {
                $this->_hook = new Hook(common: $this->_c);
            }
            $this->_hook->triggerHook(
                hookConfig: $this->_c->req->routeHook['__PRE-ROUTE-HOOKS__']
            );
        }

        if ($this->_processBeforePayload()) {
            return true;
        }

        // Load Payloads
        if (!$this->_c->req->isConfigRequest) {
            $this->_c->req->loadPayload();
        }

        $class = null;
        switch ($this->_c->req->REQUEST_METHOD) {
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

        if (!is_null(value: $class)) {
            $api = new $class(common: $this->_c);
            if ($api->init()) {
                $api->process();
            }
        }

        // Check & Process Cron / ThirdParty calls
        $this->_processAfterPayload();

        // Execute Post Route Hooks
        if (isset($this->_c->req->routeHook['__POST-ROUTE-HOOKS__'])) {
            if (is_null(value: $this->_hook)) {
                $this->_hook = new Hook(common: $this->_c);
            }
            $this->_hook->triggerHook(
                hookConfig: $this->_c->req->routeHook['__POST-ROUTE-HOOKS__']
            );
        }

        return true;
    }

    /**
     * Miscellaneous Functionality Before Collecting Payload
     *
     * @return bool
     */
    private function _processBeforePayload(): bool
    {
        $class = null;

        switch ($this->_c->req->routeElements[0]) {
        case Env::$allowRoutesRequest
            && Env::$routesRequestUri === $this->_c->req->routeElements[0]:
            $class = __NAMESPACE__ . '\\Routes';
            break;
        case Env::$allowCustomRequest
            && Env::$customRequestUriPrefix === $this->_c->req->routeElements[0]:
            $class = __NAMESPACE__ . '\\Custom';
            break;
        case Env::$allowUploadRequest
            && Env::$uploadRequestUriPrefix === $this->_c->req->routeElements[0]:
            $class = __NAMESPACE__ . '\\Upload';
            break;
        case Env::$allowThirdPartyRequest
            && Env::$thirdPartyRequestUriPrefix === $this->_c->req->routeElements[0]:
            $class = __NAMESPACE__ . '\\ThirdParty';
            break;
        case Env::$allowCacheRequest
            && Env::$cacheRequestUriPrefix === $this->_c->req->routeElements[0]:
            $class = __NAMESPACE__ . '\\CacheHandler';
            break;
        }

        $foundClass = false;
        if (!empty($class)) {
            $this->_beforePayload = true;
            $api = new $class(common: $this->_c);
            if ($api->init()) {
                $api->process();
            }
            $foundClass = true;
        }

        return $foundClass;
    }

    /**
     * Miscellaneous Functionality After Collecting Payload
     *
     * @return bool
     */
    private function _processAfterPayload(): bool
    {
        return true;
    }
}
