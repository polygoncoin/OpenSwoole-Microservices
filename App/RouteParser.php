<?php

/**
 * RouteParser
 * php version 8.3
 *
 * @category  RouteParser
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Http;
use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;
use Microservices\App\CommonFunction;
use Microservices\App\HttpStatus;

/**
 * RouteParser
 * php version 8.3
 *
 * @category  RouteParser
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RouteParser
{
	/**
	 * Array containing details of received route elements
	 *
	 * @var string[]
	 */
	public $routeElements = [];

	/**
	 * Pre / Post hooks defined in respective Route file
	 *
	 * @var string
	 */
	public $routeHook = null;

	/**
	 * Is Starting With Reserved Route Keyword Flag
	 *
	 * @var bool
	 */
	public $routeStartingWithReservedKeywordFlag = false;

	/**
	 * Route Starting Reserved Keyword
	 *
	 * @var string
	 */
	public $routeStartingReservedKeyword = '';

	/**
	 * Is Ending With Reserved Route Keyword Flag
	 *
	 * @var bool
	 */
	public $routeEndingWithReservedKeywordFlag = false;

	/**
	 * Route Ending Reserved Keyword
	 *
	 * @var string
	 */
	public $routeEndingReservedKeyword = '';

	/**
	 * Raw route / Configured Path
	 *
	 * @var string
	 */
	public $configuredRoute = '';

	/**
	 * Location of File containing code for route
	 *
	 * @var string
	 */
	public $sqlConfigFile = null;

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
	 * Parse route as per method
	 *
	 * @param string $routeFileLocation Route file
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function parseRoute($routeFileLocation = null): void
	{
		$Constant = __NAMESPACE__ . '\Constant';
		$Env = __NAMESPACE__ . '\Env';

		$this->routeElements = explode(
			separator: '/',
			string: trim(string: $this->http->iConfig['get'][ROUTE_URL_PARAM], characters: '/')
		);
		$routeLastElementPos = count(value: $this->routeElements) - 1;
		// if ($this->routeElements[$routeLastElementPos] === Env::$importSampleRequestRouteKeyword) {
		//     if (isset($this->http->iConfig['server']['httpMethod'])) {
		//         $this->http->iConfig['server']['httpMethod'] = $this->http->iConfig['server']['httpMethod'];
		//     }
		// }

		if ($routeFileLocation === null) {
			if ($this->http->req->open) {
				$routeFileLocation = Constant::$OPEN_ROUTES_DIR
					. DIRECTORY_SEPARATOR . $this->http->iConfig['server']['httpMethod'] . 'routes.php';
			} else {
				$routeFileLocation = Constant::$AUTH_ROUTES_DIR
					. DIRECTORY_SEPARATOR . 'CustomerDB'
					. DIRECTORY_SEPARATOR . 'Groups'
					. DIRECTORY_SEPARATOR . $this->http->req->s['gDetails']['name']
					. DIRECTORY_SEPARATOR . $this->http->iConfig['server']['httpMethod'] . 'routes.php';
			}
		}

		if (file_exists(filename: $routeFileLocation)) {
			$routesConfig = include $routeFileLocation;
		} else {
			throw new \Exception(
				message: 'Route file missing: ' . $this->http->iConfig['server']['httpMethod'] . ' method',
				code: HttpStatus::$InternalServerError
			);
		}

		$configuredRoute = [];

		for ($key = 0, $keyCount = count($this->routeElements); $key < $keyCount; $key++) {
			$element = $this->routeElements[$key];
			if ($element === '') {
				continue;
			}
			if (
				in_array(
					needle: $key,
					haystack: ['__PRE-ROUTE-HOOKS__', '__POST-ROUTE-HOOKS__']
				)
			) {
				$this->routeHook[$key] = $element;
				continue;
			}

			if (isset($routesConfig[$element])) { // Route element is configured
				$configuredRoute[] = $element;
				$routesConfig = &$routesConfig[$element];
				$this->checkPresenceOfDynamicString(element: $element);
				continue;
			} elseif ( // Route starting with reserved keyword
				$key === 0
				&& $this->isStartingWithReservedRouteKeyword(routeStartingKeyword: $element)
			) {
				continue;
			} elseif ( // Route ending with reserved keyword
				$key === $routeLastElementPos
				&& $this->isEndingWithReservedRouteKeyword(routeEndingKeyword: $element)
			) {
				break;
			} else { // Route element is a variable/dynamic input
				if (
					(isset($routesConfig['__FILE__']) && count(value: $routesConfig) > 2)
					|| (!isset($routesConfig['__FILE__']) && count(value: $routesConfig) > 0)
				) {
					[
						$foundIntRoute,
						$foundIntParamName,
						$foundStringRoute,
						$foundStringParamName
					] = $this->findRouteAndParamName(
						routesConfig: $routesConfig,
						element: $element
					);
					if ($foundIntRoute) {
						$configuredRoute[] = $foundIntRoute;
						$this->http->req->s['routeParams'][$foundIntParamName] =
							(int)$element;
					} elseif ($foundStringRoute) {
						$configuredRoute[] = $foundStringRoute;
						$this->http->req->s['routeParams'][$foundStringParamName] =
							urldecode(string: $element);
					} else {
						throw new \Exception(
							message: 'Route not supported',
							code: HttpStatus::$BadRequest
						);
					}
					$routesConfig = &$routesConfig[
						($foundIntRoute ? $foundIntRoute : $foundStringRoute)
					];
				} else {
					throw new \Exception(
						message: 'Route not supported',
						code: HttpStatus::$BadRequest
					);
				}
				if (
					isset($routesConfig['iRepresentation'])
					&& Env::isValidDataRep(
						dataRepresentation: $routesConfig['iRepresentation'],
						mode: 'input'
					)
				) {
					Env::$iRepresentation = $routesConfig['iRepresentation'];
				}
			}
		}

		// Input data representation over rides global and routes settings
		// Switch Input data representation if set in URL param
		if (
			Env::$enableInputRepresentationAsQueryParam
			&& isset($this->http->iConfig['get']['iRepresentation'])
			&& Env::isValidDataRep(
				dataRepresentation: $this->http->iConfig['get']['iRepresentation'],
				mode: 'input'
			)
		) {
			Env::$iRepresentation = $this->http->iConfig['get']['iRepresentation'];
		}

		$this->configuredRoute = '/' . implode(separator: '/', array: $configuredRoute);
		$this->validateConfigFile(routesConfig: $routesConfig);
	}

	/**
	 * Process Route Starting Keyword
	 *
	 * @param string $routeStartingKeyword Route Starting Keyword
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function isStartingWithReservedRouteKeyword($routeStartingKeyword)
	{
		if (
			Env::$enableCidrCheck
			&& in_array($routeStartingKeyword, Env::$reservedRoutesPrefix)
		) {
			$this->routeStartingWithReservedKeywordFlag = true;
			$this->routeStartingReservedKeyword = $routeStartingKeyword;
			$isValidIp = CommonFunction::checkCidr(
				IP: $this->http->iConfig['server']['httpRequestIP'],
				cidrString: Env::$reservedRoutesCidrString[$routeStartingKeyword]
			);
			if (!$isValidIp) {
				throw new \Exception(
					message: 'Source IP is not supported',
					code: HttpStatus::$NotFound
				);
			}
		}

		return true;
	}

	/**
	 * Process Route Ending Keyword
	 *
	 * @param string $routeEndingKeyword Route Ending Keyword
	 *
	 * @return bool
	 */
	private function isEndingWithReservedRouteKeyword($routeEndingKeyword)
	{
		$return = false;

		if (
			Env::$enableConfigRequest
			&& Env::$configRequestRouteKeyword === $routeEndingKeyword
		) {
			$this->routeEndingWithReservedKeywordFlag = true;
			$this->routeEndingReservedKeyword = Env::$configRequestRouteKeyword;
			$return = true;
		} elseif (
			Env::$enableImportRequest
			&& Env::$importRequestRouteKeyword === $routeEndingKeyword
		) {
			$this->routeEndingWithReservedKeywordFlag = true;
			$this->routeEndingReservedKeyword = Env::$importRequestRouteKeyword;
			$return = true;
		} elseif (
			Env::$enableImportSampleRequest
			&& Env::$importSampleRequestRouteKeyword === $routeEndingKeyword
		) {
			$this->routeEndingWithReservedKeywordFlag = true;
			$this->routeEndingReservedKeyword = Env::$importSampleRequestRouteKeyword;
			$return = true;
		}

		return $return;
	}

	/**
	 * Process Route Element
	 *
	 * @param string $routeElement         Configured route element
	 * @param string $element              Element
	 * @param string $foundIntRoute        Found as int route element
	 * @param string $foundIntParamName    Found as int param name
	 * @param string $foundStringRoute     Found as String route element
	 * @param string $foundStringParamName Found as String param name
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function processRouteElement(
		$routeElement,
		$dataType,
		&$element,
		&$foundIntRoute,
		&$foundIntParamName,
		&$foundStringRoute,
		&$foundStringParamName
	): bool {
		// Is a dynamic URI element
		if (strpos(haystack: $routeElement, needle: '{') !== 0) {
			return false;
		}

		$dynamicRoute = trim(string: $routeElement, characters: '{}');
		[$paramName, $paramDataType] = explode(
			separator: ':',
			string: $dynamicRoute
		);

		if (!in_array(needle: $paramDataType, haystack: ['int', 'string'])) {
			throw new \Exception(
				message: 'Invalid datatype set for Route',
				code: HttpStatus::$InternalServerError
			);
		}

		if ($paramDataType === 'int' && ctype_digit(text: $element)) {
			$foundIntRoute = $routeElement;
			$foundIntParamName = $paramName;
			DatabaseServerDataType::validateDataType(
				data: $element,
				dataType: $dataType
			);
		}
		if ($paramDataType === 'string') {
			$foundStringRoute = $routeElement;
			$foundStringParamName = $paramName;
			DatabaseServerDataType::validateDataType(
				data: $element,
				dataType: $dataType
			);
		}

		return true;
	}

	/**
	 * Validate config file
	 *
	 * @param array $routesConfig Route config
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validateConfigFile(&$routesConfig): void
	{
		// Set route code file
		if (!isset($routesConfig['__FILE__'])) {
			if (count($routesConfig) > 0) {
				throw new \Exception(
					message: 'Route not supported',
					code: HttpStatus::$BadRequest
				);
			}
			if (
				!(
					$routesConfig['__FILE__'] === false
					|| file_exists(filename: $routesConfig['__FILE__'])
				)
			) {
				throw new \Exception(
					message: 'Missing config for ' . $this->http->iConfig['server']['httpMethod'] . ' method',
					code: HttpStatus::$InternalServerError
				);
			}
		}

		if (
			!empty($routesConfig['__FILE__'])
			&& file_exists(filename: $routesConfig['__FILE__'])
		) {
			$Constant = __NAMESPACE__ . '\Constant';
			$Env = __NAMESPACE__ . '\Env';

			$this->sqlConfigFile = $routesConfig['__FILE__'];

			// Output data representation over rides global
			// Output data representation set in Query config file
			$sqlConfig = include $this->sqlConfigFile;
			if (
				isset($sqlConfig['oRepresentation'])
				&& Env::isValidDataRep(
					dataRepresentation: $sqlConfig['oRepresentation'],
					mode: 'output'
				)
			) {
				$this->http->res->oRepresentation = $sqlConfig['oRepresentation'];
			}
		}

		// Switch Output data representation if set in URL param
		if (
			Env::$enableOutputRepresentationAsQueryParam
			&& isset($this->http->iConfig['get']['oRepresentation'])
			&& Env::isValidDataRep(
				dataRepresentation: $this->http->iConfig['get']['oRepresentation'],
				mode: 'output'
			)
		) {
			$this->http->res->oRepresentation = $this->http->iConfig['get']['oRepresentation'];
		}
	}

	/**
	 * Check presence of Dynamic String in URL same as configured in Route file.
	 *
	 * @param string $element Route element
	 *
	 * @return void
	 */
	private function checkPresenceOfDynamicString($element): void
	{
		if (strpos(haystack: $element, needle: '{') === 0) {
			$param = substr(
				string: $element,
				offset: 1,
				length: strpos(haystack: $element, needle: ':') - 1
			);
			$this->http->req->s['routeParams'][$param] = $element;
		}
	}

	/**
	 * Find ROute and Param Name from Dynamic String configured in Route file.
	 *
	 * @param array  $routesConfig  Route config
	 * @param string $element Route element
	 *
	 * @return array
	 */
	private function findRouteAndParamName(&$routesConfig, &$element): array
	{
		$foundIntRoute = false;
		$foundIntParamName = false;
		$foundStringRoute = false;
		$foundStringParamName = false;
		foreach (array_keys(array: $routesConfig) as $routeElement) {
			if (in_array($routeElement, ['dataType'])) {
				continue;
			}
			if (
				strpos(haystack: $routeElement, needle: '{') === 0
				&& isset($routesConfig[$routeElement]['dataType'])
			) {
				$dataType = $routesConfig[$routeElement]['dataType'];
				// Is a dynamic URI element
				$this->processRouteElement(
					routeElement: $routeElement,
					dataType: $dataType,
					element: $element,
					foundIntRoute: $foundIntRoute,
					foundIntParamName: $foundIntParamName,
					foundStringRoute: $foundStringRoute,
					foundStringParamName: $foundStringParamName
				);
			}
		}

		return [
			$foundIntRoute,
			$foundIntParamName,
			$foundStringRoute,
			$foundStringParamName
		];
	}
}
