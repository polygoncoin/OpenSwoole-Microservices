<?php
namespace Microservices\ThirdParty;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

/**
 * Class to initialize api HTTP request
 *
 * This class process the api request
 *
 * @category   Custom API
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class ThirdPartyApi
{
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
        return $this->c->httpResponse->isSuccess();
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($this->c->httpRequest->routeElements[1]);
        $api = new $class($this->c);
        if ($api->init()) {
            $api->process();
        }

        return $this->c->httpResponse->isSuccess();
    }
}
