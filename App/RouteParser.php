<?php

/**
 * RouteParser
 * php version 8.3
 * 
 * @category  RouteParser
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
use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * RouteParser
 * php version 8.3
 * 
 * @category  RouteParser
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RouteParser
{
	/**
	 * Array containing detail of received route elements
	 * 
	 * @var string[]
	 */
	public $routeElementArray = [];

	/**
	 * Route file location
	 * 
	 * @var null|string
	 */
	public $routeFileLocation = null;

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
	 * Sql config file
	 * 
	 * @var null|string
	 */
	public $sqlConfigFile = null;

	/**
	 * Sql config
	 * 
	 * @var null|string
	 */
	public $sqlConfig = null;

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	public $httpObject = null;

	/**
	 * Reserved Routes Prefix
	 * 
	 * @var null|array
	 */
	public $reservedRoutesPrefix = null;

	/**
	 * Reserved Routes CIDR
	 * 
	 * @var null|array
	 */
	public $reservedRoutesCidrString = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
	}

	/**
	 * Parse route as per method
	 * 
	 * @param string $routeFileLocation Route file
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function parseRoute(
		$routeFileLocation = null
	): void {
		$this->routeElementArray = explode(
			separator: '/',
			string: trim(
				string: $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM],
				characters: '/'
			)
		);

		if (
			isset($this->routeElementArray[1])
			&& $this->routeElementArray[1] === Env::$dropboxRequestRoutePrefix
		) {
			if ($this->httpObject->httpRequestObject->isPrivateRequest) {
				if (
					!CommonFunction::isEnabled(
						httpObject: $this->httpObject,
						feature: 'customer_enabled_dropbox_request'
					)
				) {
					throw new \Exception(
						message: 'Route not supported',
						code: HttpStatus::$BadRequest
					);
				}
				CommonFunction::checkCidr(
					ip: $this->httpObject->httpReqData['server']['httpRequestIp'],
					cidrString: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_dropbox_request_restricted_cidr']
				);
			}
			$this->routeStartingWithReservedKeywordFlag = true;
			$this->routeStartingReservedKeyword = Env::$dropboxRequestRoutePrefix;

			$this->configuredRoute = '/' . implode(
				separator: '/',
				array: $this->routeElementArray
			);

			return;
		}
		if ($this->routeElementArray[0] === Env::$routesRequestRoute) {
			if (
				!CommonFunction::isEnabled(
					httpObject: $this->httpObject,
					feature: 'customer_enabled_routes_request'
				)
			) {
				throw new \Exception(
					message: 'Route not supported',
					code: HttpStatus::$BadRequest
				);
			}
			CommonFunction::checkCidr(
				ip: $this->httpObject->httpReqData['server']['httpRequestIp'],
				cidrString: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_routes_request_restricted_cidr']
			);

			$this->routeStartingWithReservedKeywordFlag = true;
			$this->routeStartingReservedKeyword = Env::$routesRequestRoute;

			$this->configuredRoute = '/' . implode(
				separator: '/',
				array: $this->routeElementArray
			);

			return;
		}

		$routeLastElementPos = count(
			value: $this->routeElementArray
		) - 1;
		// if ($this->routeElementArray[$routeLastElementPos] === Env::$importSampleRequestRouteKeyword) {
		//     if (isset($this->httpObject->httpReqData['server']['httpRequestMethod'])) {
		//         $this->httpObject->httpReqData['server']['httpRequestMethod'] = $this->httpObject->httpReqData['server']['httpRequestMethod'];
		//     }
		// }

		if ($routeFileLocation === Constant::$NULL) {
			if ($this->httpObject->httpRequestObject->isPrivateRequest) {
				$routeFileLocation = $this->httpObject->httpRequestObject->routesDirectory
					. DIRECTORY_SEPARATOR . 'CustomerDB'
					. DIRECTORY_SEPARATOR . 'Groups'
					. DIRECTORY_SEPARATOR . $this->httpObject->httpRequestObject->activeRequestData['groupData']['customer_user_group_name']
					. DIRECTORY_SEPARATOR . $this->httpObject->httpReqData['server']['httpRequestMethod'] . 'routes.php';
			} else {
				$routeFileLocation = $this->httpObject->httpRequestObject->routesDirectory
					. DIRECTORY_SEPARATOR . $this->httpObject->httpReqData['server']['httpRequestMethod'] . 'routes.php';
			}
		}

		if (
			file_exists(
				filename: $routeFileLocation
			)
		) {
			$Constant = __NAMESPACE__ . '\Constant';
			$Env = __NAMESPACE__ . '\Env';

			$this->routeFileLocation = $routeFileLocation;
			$routeConfig = include $routeFileLocation;
		} else {
			throw new \Exception(
				message: 'Route file missing: HTTP ' . $this->httpObject->httpReqData['server']['httpRequestMethod'] . ' method',
				code: HttpStatus::$InternalServerError
			);
		}

		$configuredRoute = [];
		$indexCount = count(
			value: $this->routeElementArray
		);
		for ($index = 0; $index < $indexCount; $index++) {
			$element = $this->routeElementArray[$index];
			if ($element === '') {
				continue;
			}
			if ($index === 0) { // Route starting with reserved keyword
				$this->isStartingWithReservedRouteKeyword(
					routeStartingKeyword: $element
				);
			}

			if (isset($routeConfig[$element])) { // Route element is configured
				if (isset($routeConfig[$element]['__PRE-ROUTE-HOOKS__'])) {
					$this->routeHook[$element]['__PRE-ROUTE-HOOKS__'] = $routeConfig[$element]['__PRE-ROUTE-HOOKS__'];
				}
				if (isset($routeConfig[$element]['__POST-ROUTE-HOOKS__'])) {
					$this->routeHook[$element]['__POST-ROUTE-HOOKS__'] = $routeConfig[$element]['__POST-ROUTE-HOOKS__'];
				}
				$configuredRoute[] = $element;
				$routeConfig = &$routeConfig[$element];
				$this->checkPresenceOfDynamicString(
					element: $element
				);
				continue;
			} elseif ( // Route ending with reserved keyword
				$index === $routeLastElementPos
				&& $this->isEndingWithReservedRouteKeyword(
					routeEndingKeyword: $element
				)
			) {
				break;
			} else { // Route element is a variable/dynamic input
				if (
					(
						isset($routeConfig['__FILE__'])
						&& count(
							value: $routeConfig
						) > 2
					)
					|| (
						!isset($routeConfig['__FILE__'])
						&& count(
							value: $routeConfig
						) > 0
					)
				) {
					[
						$foundIntRoute,
						$foundIntParamName,
						$foundStringRoute,
						$foundStringParamName
					] = $this->findRouteAndParamName(
						routeConfig: $routeConfig,
						element: $element
					);
					if ($foundIntRoute) {
						$configuredRoute[] = $foundIntRoute;
						$this->httpObject->httpRequestObject->activeRequestData['routeParamArray'][$foundIntParamName] =
							(int)$element;
					} elseif ($foundStringRoute) {
						$configuredRoute[] = $foundStringRoute;
						$this->httpObject->httpRequestObject->activeRequestData['routeParamArray'][$foundStringParamName] =
							urldecode(
								string: $element
							);
					} else {
						throw new \Exception(
							message: 'Route not supported',
							code: HttpStatus::$BadRequest
						);
					}
					$_element = $foundIntRoute ? $foundIntRoute : $foundStringRoute;
					if (isset($routeConfig[$_element]['__PRE-ROUTE-HOOKS__'])) {
						$this->routeHook[$_element]['__PRE-ROUTE-HOOKS__'] = $routeConfig[$_element]['__PRE-ROUTE-HOOKS__'];
					}
					if (isset($routeConfig[$_element]['__POST-ROUTE-HOOKS__'])) {
						$this->routeHook[$_element]['__POST-ROUTE-HOOKS__'] = $routeConfig[$_element]['__POST-ROUTE-HOOKS__'];
					}
					$routeConfig = &$routeConfig[$_element];
				} else {
					throw new \Exception(
						message: 'Route not supported',
						code: HttpStatus::$BadRequest
					);
				}
				if (
					isset($routeConfig['inputRepresentation'])
					&& Env::isValidDataRep(
						dataRepresentation: $routeConfig['inputRepresentation'],
						mode: 'input'
					)
				) {
					$this->httpObject->httpRequestObject->inputRepresentation = $routeConfig['inputRepresentation'];
				}
			}
		}

		// Input data representation over rides global and routes settings
		// Switch Input data representation if set in URL param
		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_input_representation_in_query_string'
			)
			&& isset($this->httpObject->httpReqData['get']['inputRepresentation'])
			&& Env::isValidDataRep(
				dataRepresentation: $this->httpObject->httpReqData['get']['inputRepresentation'],
				mode: 'input'
			)
		) {
			$this->httpObject->httpRequestObject->inputRepresentation = $this->httpObject->httpReqData['get']['inputRepresentation'];
		}

		$this->configuredRoute = '/' . implode(
			separator: '/',
			array: $configuredRoute
		);
		$this->validateConfigFile(
			routeConfig: $routeConfig
		);
	}

	/**
	 * Process Route Starting Keyword
	 * 
	 * @param string $routeStartingKeyword Route Starting Keyword
	 * 
	 * @return bool
	 * @throws \Exception
	 */
	private function isStartingWithReservedRouteKeyword(
		$routeStartingKeyword
	) :bool {
		$this->setReservedRouteArray();
		if (
			in_array(
				needle: $routeStartingKeyword,
				haystack: $this->reservedRoutesPrefix,
				strict: Constant::$TRUE
			)
		) {
			$this->routeStartingWithReservedKeywordFlag = true;
			$this->routeStartingReservedKeyword = $routeStartingKeyword;
			if (
				CommonFunction::isEnabled(
					httpObject: $this->httpObject,
					feature: 'customer_enabled_cidr_check'
				)
			) {
				if (isset($this->reservedRoutesCidrString[$routeStartingKeyword])) {
					CommonFunction::checkCidr(
						ip: $this->httpObject->httpReqData['server']['httpRequestIp'],
						cidrString: $this->reservedRoutesCidrString[$routeStartingKeyword]
					);
				}
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
	private function isEndingWithReservedRouteKeyword(
		$routeEndingKeyword
	): bool {
		$return = false;

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_explain_request'
			)
			&& Env::$explainRequestRouteKeyword === $routeEndingKeyword
		) {
			$this->routeEndingWithReservedKeywordFlag = true;
			$this->routeEndingReservedKeyword = Env::$explainRequestRouteKeyword;
			$return = true;
		} elseif (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_import_request'
			)
			&& Env::$importRequestRouteKeyword === $routeEndingKeyword
		) {
			$this->routeEndingWithReservedKeywordFlag = true;
			$this->routeEndingReservedKeyword = Env::$importRequestRouteKeyword;
			$return = true;
		} elseif (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_import_sample_request'
			)
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
		if (
			strpos(
				haystack: $routeElement,
				needle: '{'
			) !== 0
		) {
			return false;
		}

		$dynamicRoute = trim(
			string: $routeElement,
			characters: '{}'
		);
		[$paramName, $paramDataType] = explode(
			separator: ':',
			string: $dynamicRoute
		);

		if (
			!in_array(
				needle: $paramDataType,
				haystack: ['int', 'string'],
				strict: Constant::$TRUE
			)
		) {
			throw new \Exception(
				message: 'Invalid datatype set for Route',
				code: HttpStatus::$InternalServerError
			);
		}

		if (
			$paramDataType === 'int'
			&& ctype_digit(
				text: $element
			)
		) {
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
	 * Validate Sql config file
	 * 
	 * @param array $routeConfig Route config
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function validateConfigFile(
		&$routeConfig
	): void {
		// Set route code file
		if (!isset($routeConfig['__FILE__'])) {
			if (
				count(
					value: $routeConfig
				) > 0
			) {
				throw new \Exception(
					message: 'Route not supported',
					code: HttpStatus::$BadRequest
				);
			}
			if (
				!(
					$routeConfig['__FILE__'] === Constant::$FALSE
					|| file_exists(
						filename: $routeConfig['__FILE__']
					)
				)
			) {
				throw new \Exception(
					message: 'Missing config for HTTP ' . $this->httpObject->httpReqData['server']['httpRequestMethod'] . ' method',
					code: HttpStatus::$InternalServerError
				);
			}
		}

		if (
			!empty($routeConfig['__FILE__'])
			&& file_exists(
				filename: $routeConfig['__FILE__']
			)
		) {
			$this->sqlConfigFile = $routeConfig['__FILE__'];

			$Constant = __NAMESPACE__ . '\Constant';
			$Env = __NAMESPACE__ . '\Env';

			// Output data representation over rides global
			// Output data representation set in Query config file
			$this->sqlConfig = include $this->sqlConfigFile;
			if (
				isset($this->sqlConfig['outputRepresentation'])
				&& Env::isValidDataRep(
					dataRepresentation: $this->sqlConfig['outputRepresentation'],
					mode: 'output'
				)
			) {
				if (
					$this->sqlConfig['outputRepresentation'] === 'HTML'
					&& isset($this->sqlConfig['htmlFile'])
				) {
					$this->httpObject->httpResponseObject->outputRepresentation = $this->sqlConfig['outputRepresentation'];
					$this->httpObject->httpResponseObject->dataEncodeObject->htmlFile = $this->sqlConfig['htmlFile'];
				} elseif (
					$this->sqlConfig['outputRepresentation'] === 'PHP'
					&& isset($this->sqlConfig['phpFile'])
				) {
					$this->httpObject->httpResponseObject->outputRepresentation = $this->sqlConfig['outputRepresentation'];
					$this->httpObject->httpResponseObject->dataEncodeObject->phpFile = $this->sqlConfig['phpFile'];
				} elseif (
					$this->sqlConfig['outputRepresentation'] === 'XSLT'
					&& isset($this->sqlConfig['xsltFile'])
				) {
					$this->httpObject->httpResponseObject->outputRepresentation = $this->sqlConfig['outputRepresentation'];
					$this->httpObject->httpResponseObject->dataEncodeObject->xsltFile = $this->sqlConfig['xsltFile'];
				} elseif (
					!in_array(
						needle: $this->sqlConfig['outputRepresentation'],
						haystack: ['HTML', 'PHP', 'XSLT'],
						strict: Constant::$TRUE
					)
				) {
					$this->httpObject->httpResponseObject->outputRepresentation = $this->httpObject->httpReqData['get']['outputRepresentation'];
				}
			}
		}

		// Switch Output data representation if set in URL param
		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_output_representation_in_query_string'
			)
			&& isset($this->httpObject->httpReqData['get']['outputRepresentation'])
			&& Env::isValidDataRep(
				dataRepresentation: $this->httpObject->httpReqData['get']['outputRepresentation'],
				mode: 'output'
			)
		) {
			if (
				$this->httpObject->httpReqData['get']['outputRepresentation'] === 'HTML'
				&& isset($this->sqlConfig['htmlFile'])
			) {
				$this->httpObject->httpResponseObject->outputRepresentation = $this->httpObject->httpReqData['get']['outputRepresentation'];
				$this->httpObject->httpResponseObject->dataEncodeObject->htmlFile = $this->sqlConfig['htmlFile'];
			} elseif (
				$this->httpObject->httpReqData['get']['outputRepresentation'] === 'PHP'
				&& isset($this->sqlConfig['phpFile'])
			) {
				$this->httpObject->httpResponseObject->outputRepresentation = $this->httpObject->httpReqData['get']['outputRepresentation'];
				$this->httpObject->httpResponseObject->dataEncodeObject->phpFile = $this->sqlConfig['phpFile'];
			} elseif (
				$this->httpObject->httpReqData['get']['outputRepresentation'] === 'XSLT'
				&& isset($this->sqlConfig['xsltFile'])
			) {
				$this->httpObject->httpResponseObject->outputRepresentation = $this->httpObject->httpReqData['get']['outputRepresentation'];
				$this->httpObject->httpResponseObject->dataEncodeObject->xsltFile = $this->sqlConfig['xsltFile'];
			} elseif (
				!in_array(
					needle: $this->httpObject->httpReqData['get']['outputRepresentation'],
					haystack: ['HTML', 'PHP', 'XSLT'],
					strict: Constant::$TRUE
				)
			) {
				$this->httpObject->httpResponseObject->outputRepresentation = $this->httpObject->httpReqData['get']['outputRepresentation'];
			}
		}
	}

	/**
	 * Check presence of Dynamic String in URL same as configured in Route file.
	 * 
	 * @param string $element Route element
	 * 
	 * @return void
	 */
	private function checkPresenceOfDynamicString(
		$element
	): void {
		if (
			strpos(
				haystack: $element,
				needle: '{'
			) === 0
		) {
			$param = substr(
				string: $element,
				offset: 1,
				length: strpos(
					haystack: $element,
					needle: ':'
				) - 1
			);
			$this->httpObject->httpRequestObject->activeRequestData['routeParamArray'][$param] = $element;
		}
	}

	/**
	 * Find Ruute and Param Name from Dynamic String configured in Route file.
	 * 
	 * @param array  $routeConfig Route config
	 * @param string $element     Route element
	 * 
	 * @return array
	 */
	private function findRouteAndParamName(
		&$routeConfig,
		&$element
	): array {
		$foundIntRoute = false;
		$foundIntParamName = false;
		$foundStringRoute = false;
		$foundStringParamName = false;
		foreach (
			array_keys(
				array: $routeConfig
			) as $routeElement
		) {
			if (
				in_array(
					needle: $routeElement,
					haystack: ['dataType'],
					strict: Constant::$TRUE
				)
			) {
				continue;
			}
			if (
				strpos(
					haystack: $routeElement,
					needle: '{'
				) === 0
				&& isset($routeConfig[$routeElement]['dataType'])
			) {
				$dataType = $routeConfig[$routeElement]['dataType'];
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

	/**
	 * Set Reserved Route
	 * 
	 * @return void
	 */
	private function setReservedRouteArray(): void
	{
		$this->reservedRoutesPrefix = [
			Env::$cronRequestRoutePrefix,
			Env::$reloadRequestRoutePrefix,
			Env::$routesRequestRoute
		];

		$this->reservedRoutesCidrString = [
			Env::$cronRequestRoutePrefix => $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_cron_request_restricted_cidr'],
			Env::$reloadRequestRoutePrefix => Env::$reloadRestrictedCidr,
			Env::$routesRequestRoute => $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_routes_request_restricted_cidr']
		];

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_custom_request'
			)
		) {
			$this->reservedRoutesPrefix[] = Env::$customRequestRoutePrefix;
			$this->reservedRoutesCidrString[Env::$customRequestRoutePrefix] = $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_custom_request_restricted_cidr'];
		}
		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_thirdparty_request'
			)
		) {
			$this->reservedRoutesPrefix[] = Env::$thirdPartyRequestRoutePrefix;
			$this->reservedRoutesCidrString[Env::$thirdPartyRequestRoutePrefix] = $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_thirdparty_request_restricted_cidr'];
		}
		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_upload_request'
			)
		) {
			$this->reservedRoutesPrefix[] = Env::$uploadRequestRoutePrefix;
			$this->reservedRoutesCidrString[Env::$uploadRequestRoutePrefix] = $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_upload_request_restricted_cidr'];
		}
	}
}
