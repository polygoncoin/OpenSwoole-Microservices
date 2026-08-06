<?php

/**
 * Route - Available routeArray
 * php version 8.3
 * 
 * @category  Route
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Route - Available routeArray
 * php version 8.3
 * 
 * @category  Route
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Route
{
	/**
	 * Supported HTTP methods of routeArray
	 * 
	 * @var array
	 */
	private $httpMethodArray = null;

	/**
	 * Route folder
	 * 
	 * @var string
	 */
	private $routesFolder = DIRECTORY_SEPARATOR . 'Config'
		. DIRECTORY_SEPARATOR . 'Route';

	/**
	 * Route config ignore key's
	 * 
	 * @var array
	 */
	private $reservedKeyArray = ['dataType'];

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
		$this->httpMethodArray = [
			Constant::$GET,
			Constant::$QUERY,
			Constant::$POST,
			Constant::$PUT,
			Constant::$PATCH,
			Constant::$DELETE
		];
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_routes_request'
			)
		) {
			return Constant::$TRUE;
		}

		return Constant::$FALSE;
	}

	/**
	 * Make allowed routeArray list of a logged-in user
	 * 
	 * @return mixed
	 */
	public function process(): mixed
	{
		$httpRouteArray = [];
		if ($this->httpObject->httpRequestObject->isPublicRequest) {
			$userRoutesFolder = Constant::$WWW . $this->routesFolder
				. DIRECTORY_SEPARATOR . 'Public';
		} else {
			$userRoutesFolder = Constant::$WWW . $this->routesFolder
				. DIRECTORY_SEPARATOR . 'Private'
				. DIRECTORY_SEPARATOR . 'CustomerDB'
				. DIRECTORY_SEPARATOR . 'Groups'
				. DIRECTORY_SEPARATOR . $this->httpObject->httpRequestObject->activeRequestData['groupData']['customer_user_group_name'];
		}

		foreach ($this->httpMethodArray as $httpRequestMethod) {
			$httpRouteArray[$httpRequestMethod] = [];
			$routeFileLocation =  $userRoutesFolder
				. DIRECTORY_SEPARATOR . $httpRequestMethod . 'routes.php';
			if (
				!file_exists(
					filename: $routeFileLocation
				)
			) {
				throw new \Exception(
					message: json_encode(
						value: [$routeFileLocation]
					),
					code: HttpStatus::$BadRequest
				);
				continue;
			}
			$Constant = __NAMESPACE__ . '\Constant';
			$Env = __NAMESPACE__ . '\Env';

			$routeArray = include $routeFileLocation;
			$route = '';
			$this->getRoutes(
				routeArray: $routeArray,
				route: $route,
				httpRouteArray: $httpRouteArray[$httpRequestMethod]
			);
		}
		$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
			objectKey: 'Results',
			data: $httpRouteArray
		);

		return Constant::$TRUE;
	}

	/**
	 * Create Route list
	 * 
	 * @param array  $routeArray     Route
	 * @param string $route          Current Route
	 * @param array  $httpRouteArray All HTTP Route
	 * 
	 * @return void
	 */
	private function getRoutes(
		&$routeArray,
		$route,
		&$httpRouteArray
	): void {
		foreach ($routeArray as $routeElement => &$_routeArray) {
			if (
				in_array(
					needle: $routeElement,
					haystack: $this->reservedKeyArray,
					strict: Constant::$TRUE
				)
			) {
				continue;
			}
			if ($routeElement === '__FILE__') {
				$httpRouteArray[] = $route;
			}
			if (
				is_array(
					value: $_routeArray
				)
			) {
				$_route = $route . '/' . $routeElement;
				$this->getRoutes(
					routeArray: $_routeArray,
					route: $_route,
					httpRouteArray: $httpRouteArray
				);
			}
		}
	}
}
