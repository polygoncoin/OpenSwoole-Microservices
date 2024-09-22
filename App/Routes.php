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
 * @category   Routes
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Routes
{
    /**
     * Supported HTTP methods of routes
     *
     * @var array
     */
    private $httpMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE'
    ];

    /**
     * Routes folder
     *
     * @var string
     */
    private $routesFolder = '/Config/Routes';

    /**
     * Route config ignore keys
     *
     * @var array
     */
    private $reservedKeys = [];

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
        return true;
    }

    /**
     * Make allowed routes list of a logged-in user
     *
     * @return boolean
     */
    public function process()
    {
        $httpRoutes = [];
        $userRoutesFolder = Constants::$DOC_ROOT . $this->routesFolder . '/' . $this->c->httpRequest->input['readOnlySession']['group_name'];

        foreach ($this->httpMethods as $method) {
            $httpRoutes[$method] = [];
            $routeFileLocation =  $userRoutesFolder . '/' . $method . 'routes.php';
            if (!file_exists($routeFileLocation)) {
                continue;
            }
            $routes = include $routeFileLocation;
            $route = '';
            $this->getRoutes($routes, $route, $httpRoutes[$method]);
        }
        $this->c->httpResponse->jsonEncode->addKeyValue('Results', $httpRoutes);

        return true;
    }

    /**
     * Create Routes list.
     *
     * @return void
     */
    private function getRoutes(&$routes, $route, &$httpRoutes)
    {
        foreach ($routes as $key => &$r) {
            if (in_array($key, $this->reservedKeys)) {
                continue;
            }
            if (
                Env::$allowConfigRequest &&
                $key === 'config' &&
                $r === true
            ) {
                $httpRoutes[] = $route . '/' . $key;
            }
            if ($key === '__file__') {
                $httpRoutes[] = $route;
            }
            if (is_array($r)) {
                $_route = $route . '/' . $key;
                $this->getRoutes($r, $_route, $httpRoutes);
            }
        }
    }
}
