<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\Cron\CronInterface;

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
     * @var null|CronInterface
     */
    private $api = null;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

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
        $this->c->httpRequest->init();

        $routeFileLocation = Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'CommonRoutes' . DIRECTORY_SEPARATOR . 'Cron' . DIRECTORY_SEPARATOR . $this->c->httpRequest->REQUEST_METHOD . 'routes.php';
        $this->c->httpRequest->parseRoute($routeFileLocation);

        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $class = 'Microservices\\Cron\\' . ucfirst($this->c->httpRequest->routeElements[1]);

        $this->api = new $class($this->c);
        if ($this->api->init()) {
            $this->api->process();
        }

        return true;
    }
}
