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
		return Constant::$TRUE;
	}

	/**
	 * Make allowed routeArray list of a logged-in user
	 * 
	 * @return mixed
	 */
	public function process(): mixed
	{
		$httpRouteArray = [];
		
		foreach ($this->httpMethodArray as $httpRequestMethod) {
			$configDir = Constant::$CONFIG_DIRECTORY
				. DIRECTORY_SEPARATOR . $this->httpObject->Env->CUSTOMER_CONFIG_DIRECTORY;
			$commonConfigDir = Constant::$CONFIG_DIRECTORY
				. DIRECTORY_SEPARATOR . 'Common';

			if (
				$this->httpObject->request->isPrivateWebRequest
				|| $this->httpObject->request->isPrivateApiRequest
			) {
				$configDir .= DIRECTORY_SEPARATOR . 'Private';
				$commonConfigDir .= DIRECTORY_SEPARATOR . 'Private';

				$servingFileDir = $configDir . DIRECTORY_SEPARATOR . 'ServingFile';
				$routeDir = $configDir . DIRECTORY_SEPARATOR . 'Route'
					. DIRECTORY_SEPARATOR . "GroupId.{$this->httpObject->httpReqData['active']['customerUserGroupId']}"
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					);
				$sqlDir = $configDir . DIRECTORY_SEPARATOR . 'Sql'
					. DIRECTORY_SEPARATOR . "GroupId.{$this->httpObject->httpReqData['active']['customerUserGroupId']}"
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					)
					. DIRECTORY_SEPARATOR . $this->httpObject->httpReqData['server']['httpRequestMethod'];

				$commonRouteDir = $commonConfigDir . DIRECTORY_SEPARATOR . 'Route'
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					);
				$commonSqlDir = $commonConfigDir . DIRECTORY_SEPARATOR . 'Sql'
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					)
					. DIRECTORY_SEPARATOR . $this->httpObject->httpReqData['server']['httpRequestMethod'];
			} else {
				$configDir .= DIRECTORY_SEPARATOR . 'Public';
				$commonConfigDir .= DIRECTORY_SEPARATOR . 'Public';

				$servingFileDir = $configDir . DIRECTORY_SEPARATOR . 'ServingFile';
				$routeDir = $configDir . DIRECTORY_SEPARATOR . 'Route'
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					);
				$sqlDir = $configDir . DIRECTORY_SEPARATOR . 'Sql'
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					)
					. DIRECTORY_SEPARATOR . $this->httpObject->httpReqData['server']['httpRequestMethod'];

				$commonRouteDir = $commonConfigDir . DIRECTORY_SEPARATOR . 'Route'
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					);
				$commonSqlDir = $commonConfigDir . DIRECTORY_SEPARATOR . 'Sql'
					. DIRECTORY_SEPARATOR . $this->httpObject->request->getDataMode(
						httpRequestMethod: $httpRequestMethod
					)
					. DIRECTORY_SEPARATOR . $this->httpObject->httpReqData['server']['httpRequestMethod'];
			}

			$this->httpObject->httpReqData['active']['servingFileDir'] = $servingFileDir;
			$this->httpObject->httpReqData['active']['configDir'] = $configDir;
			$this->httpObject->httpReqData['active']['routeDir'] = $routeDir;
			$this->httpObject->httpReqData['active']['sqlDir'] = $sqlDir;

			$this->httpObject->httpReqData['active']['commonServingFileDir'] = $servingFileDir;
			$this->httpObject->httpReqData['active']['commonConfigDir'] = $configDir;
			$this->httpObject->httpReqData['active']['commonRouteDir'] = $commonRouteDir;
			$this->httpObject->httpReqData['active']['commonSqlDir'] = $commonSqlDir;

			$httpRouteArray[$httpRequestMethod] = [];
			if (!is_dir($this->httpObject->httpReqData['active']['routeDir'])) {
				continue;
			}
			$routeFileLocation = $this->httpObject->httpReqData['active']['routeDir']
				. DIRECTORY_SEPARATOR . $httpRequestMethod . 'routes.php';
			if (
				!file_exists(
					filename: $routeFileLocation
				)
			) {
				throw new \Exception(
					message: 'Route file missing: '. $routeFileLocation,
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
		$this->httpObject->response->encodeObject->addKeyData(
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
