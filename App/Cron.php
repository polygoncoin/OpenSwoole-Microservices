<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\Cron\CronApi;

/**
 * Class to initiate custom API's
 *
 * @category   Cron API's
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Cron
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
        $this->c = $common;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        if ($this->c->httpResponse->isSuccess()) $this->c->httpRequest->init();

        $routeFileLocation = Constants::$DOC_ROOT . '/Config/Routes/Common/Cron/' . $this->c->httpRequest->REQUEST_METHOD . 'routes.php';
        if ($this->c->httpResponse->isSuccess()) $this->c->httpRequest->parseRoute($routeFileLocation);

        return $this->c->httpResponse->isSuccess();
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $api = new CronApi($this->c);
        if ($api->init()) {
            $api->process();
        }

        return $this->c->httpResponse->isSuccess();
    }
}
