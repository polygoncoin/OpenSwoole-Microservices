<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Hook;

/**
 * Class to initialize api HTTP request
 *
 * This class process the api request
 *
 * @category   API
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Api
{
    /**
     * Route matched for processing before payload was collected
     *
     * @var null|boolean
     */
    private $beforePayload = null;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Hook Object
     *
     * @var null|Hook
     */
    private $hook = null;

    /**
     * Constructor
     *
     * @param Common $common
     */
    public function __construct(&$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->c->httpRequest->loadClientDetails();

        if (!$this->c->httpRequest->open) {
            $this->c->httpRequest->auth->loadUserDetails();
            $this->c->httpRequest->auth->loadGroupDetails();
        }

        $this->c->httpRequest->parseRoute();
        $this->c->httpRequest->setDatabaseCacheKey();

        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        // Execute Pre Route Hooks
        if (isset($this->c->httpRequest->routeHook['__PRE-ROUTE-HOOKS__'])) {
            if (is_null($this->hook)) {
                $this->hook = new Hook($this->c);
            }
            $this->hook->triggerHook($this->c->httpRequest->routeHook['__PRE-ROUTE-HOOKS__']);
        }

        if ($this->processBeforePayload()) {
            return true;
        }

        // Load Payloads
        if (!$this->c->httpRequest->isConfigRequest) {
            $this->c->httpRequest->loadPayload();
        }

        $class = null;
        switch ($this->c->httpRequest->REQUEST_METHOD) {
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

        if (!is_null($class)) {
            $api = new $class($this->c);
            if ($api->init()) {
                $api->process();
            }
        }

        // Check & Process Cron / ThirdParty calls
        $this->processAfterPayload();

        // Execute Post Route Hooks
        if (isset($this->c->httpRequest->routeHook['__POST-ROUTE-HOOKS__'])) {
            if (is_null($this->hook)) {
                $this->hook = new Hook($this->c);
            }
            $this->hook->triggerHook($this->c->httpRequest->routeHook['__POST-ROUTE-HOOKS__']);
        }

        return true;
    }

    /**
     * Miscellaneous Functionality Before Collecting Payload
     *
     * @return boolean
     */
    private function processBeforePayload()
    {
        $class = null;

        switch ($this->c->httpRequest->routeElements[0]) {

            case Env::$allowRoutesRequest && Env::$routesRequestUri === $this->c->httpRequest->routeElements[0]:
                $class = __NAMESPACE__ . '\\Routes';
                break;
            case Env::$allowCustomRequest && Env::$customRequestUriPrefix === $this->c->httpRequest->routeElements[0]:
                $class = __NAMESPACE__ . '\\Custom';
                break;
            case Env::$allowUploadRequest && Env::$uploadRequestUriPrefix === $this->c->httpRequest->routeElements[0]:
                $class = __NAMESPACE__ . '\\Upload';
                break;
            case Env::$allowThirdPartyRequest && Env::$thirdPartyRequestUriPrefix === $this->c->httpRequest->routeElements[0]:
                $class = __NAMESPACE__ . '\\ThirdParty';
                break;
            case Env::$allowCacheRequest && Env::$cacheRequestUriPrefix === $this->c->httpRequest->routeElements[0]:
                $class = __NAMESPACE__ . '\\CacheHandler';
                break;
        }

        $foundClass = false;
        if (!empty($class)) {
            $this->beforePayload = true;
            $api = new $class($this->c);
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
     * @return boolean
     */
    private function processAfterPayload()
    {
        return true;
    }
}
