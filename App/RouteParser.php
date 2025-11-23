<?php

/**
 * RouteParser
 * php version 8.3
 *
 * @category  RouteParser
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\Constants;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

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
     * Is a config request flag
     *
     * @var bool
     */
    public $isConfigRequest = false;

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
     * Constructor
     */
    public function __construct()
    {
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
        $Constants = __NAMESPACE__ . '\Constants';
        $Env = __NAMESPACE__ . '\Env';

        if ($routeFileLocation === null) {
            if (Common::$req->open) {
                $routeFileLocation = Constants::$OPEN_ROUTES_DIR .
                    DIRECTORY_SEPARATOR . Common::$req->METHOD . 'routes.php';
            } else {
                $routeFileLocation = Constants::$AUTH_ROUTES_DIR .
                    DIRECTORY_SEPARATOR . 'ClientDB' .
                    DIRECTORY_SEPARATOR . 'Groups' .
                    DIRECTORY_SEPARATOR . Common::$req->s['gDetails']['name'] .
                    DIRECTORY_SEPARATOR . Common::$req->METHOD . 'routes.php';
            }
        }

        if (file_exists(filename: $routeFileLocation)) {
            $routes = include $routeFileLocation;
        } else {
            throw new \Exception(
                message: 'Route file missing: ' . Common::$req->METHOD . ' method',
                code: HttpStatus::$InternalServerError
            );
        }

        $this->routeElements = explode(
            separator: '/',
            string: trim(string: Common::$req->ROUTE, characters: '/')
        );
        $routeLastElementPos = count(value: $this->routeElements) - 1;
        $configuredRoute = [];

        foreach ($this->routeElements as $key => $element) {
            if (
                in_array(
                    needle: $key,
                    haystack: ['__PRE-ROUTE-HOOKS__', '__POST-ROUTE-HOOKS__']
                )
            ) {
                $this->routeHook[$key] = $element;
                continue;
            }
            $pos = false;
            if (isset($routes[$element])) {
                $configuredRoute[] = $element;
                $routes = &$routes[$element];
                $this->checkPresenceOfDynamicString(element: $element);
                continue;
            } elseif (
                $key === $routeLastElementPos
                && Env::$allowConfigRequest == 1
                && Env::$configRequestRouteKeyword === $element
            ) {
                $this->isConfigRequest = true;
                break;
            } else {
                if (
                    (isset($routes['__FILE__']) && count(value: $routes) > 1)
                    || (!isset($routes['__FILE__']) && count(value: $routes) > 0)
                ) {
                    [
                        $foundIntRoute,
                        $foundIntParamName,
                        $foundStringRoute,
                        $foundStringParamName
                    ] = $this->findRouteAndParamName(
                        routes: $routes,
                        element: $element
                    );
                    if ($foundIntRoute) {
                        $configuredRoute[] = $foundIntRoute;
                        Common::$req->s['routeParams'][$foundIntParamName]
                            = (int)$element;
                    } elseif ($foundStringRoute) {
                        $configuredRoute[] = $foundStringRoute;
                        Common::$req->s['routeParams'][$foundStringParamName]
                            = urldecode(string: $element);
                    } else {
                        throw new \Exception(
                            message: 'Route not supported',
                            code: HttpStatus::$BadRequest
                        );
                    }
                    $routes = &$routes[
                        ($foundIntRoute ? $foundIntRoute : $foundStringRoute)
                    ];
                } else {
                    throw new \Exception(
                        message: 'Route not supported',
                        code: HttpStatus::$BadRequest
                    );
                }
                if (
                    isset($routes['iRepresentation'])
                    && Env::isValidDataRep(
                        dataRepresentation: $routes['iRepresentation'],
                        mode: 'input'
                    )
                ) {
                    Env::$iRepresentation = $routes['iRepresentation'];
                }
            }
        }

        // Input data representation over rides global and routes settings
        // Switch Input data representation if set in URL param
        if (
            Env::$allowGetRepresentation == 1
            && isset(Common::$req->http['get']['iRepresentation'])
            && Env::isValidDataRep(
                dataRepresentation: Common::$req->http['get']['iRepresentation'],
                mode: 'input'
            )
        ) {
            Env::$iRepresentation = Common::$req->http['get']['iRepresentation'];
        }

        $this->configuredRoute = '/' . implode(separator: '/', array: $configuredRoute);
        $this->validateConfigFile(routes: $routes);
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
            DatabaseDataTypes::validateDataType(
                data: $element,
                dataType: $dataType
            );
        }
        if ($paramDataType === 'string') {
            $foundStringRoute = $routeElement;
            $foundStringParamName = $paramName;
            DatabaseDataTypes::validateDataType(
                data: $element,
                dataType: $dataType
            );
        }

        return true;
    }

    /**
     * Validate config file
     *
     * @param array $routes Routes config
     *
     * @return void
     * @throws \Exception
     */
    private function validateConfigFile(&$routes): void
    {
        // Set route code file
        if (
            !(isset($routes['__FILE__'])
            && ($routes['__FILE__'] === false
            || file_exists(filename: $routes['__FILE__'])))
        ) {
            throw new \Exception(
                message: 'Missing config for ' . Common::$req->METHOD . ' method',
                code: HttpStatus::$InternalServerError
            );
        }

        if (
            !empty($routes['__FILE__'])
            && file_exists(filename: $routes['__FILE__'])
        ) {
            $this->sqlConfigFile = $routes['__FILE__'];

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
                Env::$oRepresentation = $sqlConfig['oRepresentation'];
            }
        }

        // Switch Output data representation if set in URL param
        if (
            Env::$allowGetRepresentation == 1
            && isset(Common::$req->http['get']['oRepresentation'])
            && Env::isValidDataRep(
                dataRepresentation: Common::$req->http['get']['oRepresentation'],
                mode: 'output'
            )
        ) {
            Env::$oRepresentation = Common::$req->http['get']['oRepresentation'];
        }
    }

    /**
     * Check presence of Dynamic String in URL same as configured in Route file.
     *
     * @param string $element Routes element
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
            Common::$req->s['routeParams'][$param] = $element;
        }
    }

    /**
     * Find ROute and Param Name from Dynamic String configured in Route file.
     *
     * @param array  $routes  Routes config
     * @param string $element Routes element
     *
     * @return array
     */
    private function findRouteAndParamName(&$routes, &$element): array
    {
        $foundIntRoute = false;
        $foundIntParamName = false;
        $foundStringRoute = false;
        $foundStringParamName = false;
        foreach (array_keys(array: $routes) as $routeElement) {
            if (
                strpos(haystack: $routeElement, needle: '{') === 0
                && isset($routes[$routeElement]['dataType'])
            ) {
                $dataType = $routes[$routeElement]['dataType'];
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
