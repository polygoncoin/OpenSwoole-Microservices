<?php

/**
 * Route - Available routes
 * php version 8.3
 *
 * @category  Route
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Http;
use Microservices\App\Env;

/**
 * Route - Available routes
 * php version 8.3
 *
 * @category  Route
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Route
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
	 * Route folder
	 *
	 * @var string
	 */
	private $routesFolder = DIRECTORY_SEPARATOR . 'Config'
		. DIRECTORY_SEPARATOR . 'Route';

	/**
	 * Route config ignore keys
	 *
	 * @var array
	 */
	private $reservedKeys = ['dataType'];

	/**
	 * Http Object
	 *
	 * @var null|Http
	 */
	private $http = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		if (Env::$enableRoutesRequest) {
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
		$Constant = __NAMESPACE__ . '\Constant';
		$Env = __NAMESPACE__ . '\Env';

		$httpRoutes = [];
		if ($this->http->req->isOpenToWebRequest) {
			$userRoutesFolder = Constant::$WWW . $this->routesFolder
				. DIRECTORY_SEPARATOR . 'Open';
		} else {
			$userRoutesFolder = Constant::$WWW . $this->routesFolder
				. DIRECTORY_SEPARATOR . 'Auth'
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . $this->http->req->s['gDetails']['name'];
		}

		foreach ($this->httpMethods as $method) {
			$httpRoutes[$method] = [];
			$routeFileLocation =  $userRoutesFolder
				. DIRECTORY_SEPARATOR . $method . 'routes.php';
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
		$this->http->res->dataEncode->addKeyData(
			key: 'Results',
			data: $httpRoutes
		);

		return true;
	}

	/**
	 * Create Route list
	 *
	 * @param array  $routes     Route
	 * @param string $route      Current Route
	 * @param array  $httpRoutes All HTTP Route
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
				$_route = $route . '/' . $key;
				$this->getRoutes(
					routes: $r,
					route: $_route,
					httpRoutes: $httpRoutes
				);
			}
		}
	}
}
