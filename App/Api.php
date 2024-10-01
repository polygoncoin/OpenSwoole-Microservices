<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

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
     * Route matched for processing before payload was collected.
     * 
     * @var boolean
     */
    private $beforePayload = null;

    /**
     * Microservices Collection of Common Objects
     * 
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Constructor
     * 
     * @param Microservices\App\Common $common
     */
    public function __construct(Common &$common)
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
        $this->c->httpRequest->checkHost();
        $this->c->httpRequest->loadToken();
        $this->c->httpRequest->initSession();
        $this->c->httpRequest->parseRoute();

        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        // Check & Process Upload
        {
            if ($this->processBeforePayload()) {
                return true;
            }    
        }

        // Load Payloads
        if (!Env::$isConfigRequest) {
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

        // Check & Process Cron / ThirdParty calls.
        {
            $this->processAfterPayload();
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

            case 'routes':
                $class = __NAMESPACE__ . '\\Routes';
                break;
            case 'check':
                $class = __NAMESPACE__ . '\\Check';
                break;
            case 'custom':
                $class = __NAMESPACE__ . '\\Custom';
                break;
            case 'upload':
                $class = __NAMESPACE__ . '\\Upload';
                break;
            case 'thirdParty':
                $class = __NAMESPACE__ . '\\ThirdParty';
                break;
            case 'cache':
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
