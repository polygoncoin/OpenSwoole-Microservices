<?php
/**
 * Initialize Cron
 * php version 8.3
 *
 * @category  Cron
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
use Microservices\Supplement\Cron\CronInterface;

/**
 * Cron API
 * php version 8.3
 *
 * @category  CronAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Cron
{
    /**
     * Cron API object
     *
     * @var null|CronInterface
     */
    private $_api = null;

    /**
     * Common object
     *
     * @var null|Common
     */
    private $_c = null;

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
        $this->_c->initRequest();

        $routeFileLocation = Constants::$PUBLIC_HTML .
            DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Routes' .
            DIRECTORY_SEPARATOR . 'Auth' .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Common' .
            DIRECTORY_SEPARATOR . 'Cron' .
            DIRECTORY_SEPARATOR . $this->_c->req->METHOD . 'routes.php';
        $this->_c->req->parseRoute(routeFileLocation: $routeFileLocation);

        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        $class = 'Microservices\\Supplement\\Cron\\' .
            ucfirst(string: $this->_c->req->rParser->routeElements[1]);

        $this->_api = new $class(common: $this->_c);
        if ($this->_api->init()) {
            $this->_api->process();
        }

        return true;
    }
}
