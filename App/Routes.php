<?php

/**
 * Routes - Available routes
 * php version 8.3
 *
 * @category  Routes
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

/**
 * Routes - Available routes
 * php version 8.3
 *
 * @category  Routes
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
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
    private $routesFolder = DIRECTORY_SEPARATOR . 'Config' .
        DIRECTORY_SEPARATOR . 'Routes';

    /**
     * Route config ignore keys
     *
     * @var array
     */
    private $reservedKeys = [];

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        if (Env::$allowRoutesRequest) {
            return true;
        }
        return false;
    }

    /**
     * Make allowed routes list of a logged-in user
     *
     * @param array $payload Payload
     *
     * @return bool
     */
    public function process(array $payload = []): bool
    {
        $Constants = __NAMESPACE__ . '\Constants';
        $Env = __NAMESPACE__ . '\Env';

        $httpRoutes = [];
        if ($this->api->req->open) {
            $userRoutesFolder = Constants::$PUBLIC_HTML . $this->routesFolder .
                DIRECTORY_SEPARATOR . 'Open';
        } else {
            $userRoutesFolder = Constants::$PUBLIC_HTML . $this->routesFolder .
                DIRECTORY_SEPARATOR . 'Auth' .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . $this->api->req->s['gDetails']['name'];
        }

        foreach ($this->httpMethods as $method) {
            $httpRoutes[$method] = [];
            $routeFileLocation =  $userRoutesFolder .
                DIRECTORY_SEPARATOR . $method . 'routes.php';
            if (!file_exists(filename: $routeFileLocation)) {
                continue;
            }
            $routes = include $routeFileLocation;
            $route = '';
            $this->getRoutes(
                routes: $routes,
                route: $route,
                httpRoutes: $httpRoutes[$method]
            );
        }
        $this->api->res->dataEncode->addKeyData(
            key: 'Results',
            data: $httpRoutes
        );

        return true;
    }

    /**
     * Create Routes list
     *
     * @param array  $routes     Routes
     * @param string $route      Current Route
     * @param array  $httpRoutes All HTTP Routes
     *
     * @return void
     */
    private function getRoutes(&$routes, $route, &$httpRoutes): void
    {
        foreach ($routes as $key => &$r) {
            if (in_array(needle: $key, haystack: $this->reservedKeys)) {
                continue;
            }
            if ($key === '__FILE__') {
                $httpRoutes[] = $route;
            }
            if (is_array(value: $r)) {
                $route = $route . '/' . $key;
                $this->getRoutes(
                    routes: $r,
                    route: $route,
                    httpRoutes: $httpRoutes
                );
            }
        }
    }
}
