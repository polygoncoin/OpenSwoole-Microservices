<?php
/**
 * RouteParser
 * php version 8.3
 *
 * @category  RouteParser
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * RouteParser
 * php version 8.3
 *
 * @category  RouteParser
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RouteParser extends DbFunctions
{
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

        if (is_null(value: $routeFileLocation)) {
            if ($this->open) {
                $routeFileLocation = Constants::$DOC_ROOT .
                    DIRECTORY_SEPARATOR . 'Config' .
                    DIRECTORY_SEPARATOR . 'Routes' .
                    DIRECTORY_SEPARATOR . 'Open' .
                    DIRECTORY_SEPARATOR . $this->REQUEST_METHOD . 'routes.php';
            } else {
                $routeFileLocation = Constants::$DOC_ROOT .
                    DIRECTORY_SEPARATOR . 'Config' .
                    DIRECTORY_SEPARATOR . 'Routes' .
                    DIRECTORY_SEPARATOR . 'Auth' .
                    DIRECTORY_SEPARATOR . 'ClientDB' .
                    DIRECTORY_SEPARATOR . 'Groups' .
                    DIRECTORY_SEPARATOR . $this->sess['groupDetails']['name'] .
                    DIRECTORY_SEPARATOR . $this->REQUEST_METHOD . 'routes.php';
            }
        }

        if (file_exists(filename: $routeFileLocation)) {
            $routes = include $routeFileLocation;
        } else {
            throw new \Exception(
                message: 'Route file missing: ' . $this->REQUEST_METHOD . ' method',
                code: HttpStatus::$InternalServerError
            );
        }

        $this->routeElements = explode(
            separator: '/',
            string: trim(string: $this->ROUTE, characters: '/')
        );
        $routeLastElementPos = count(value: $this->routeElements) - 1;
        $configuredUri = [];

        foreach ($this->routeElements as $key => $element) {
            if (in_array(
                needle: $key,
                haystack: ['__PRE-ROUTE-HOOKS__', '__POST-ROUTE-HOOKS__']
            )
            ) {
                $this->routeHook[$key] = $element;
                continue;
            }
            $pos = false;
            if (isset($routes[$element])) {
                $configuredUri[] = $element;
                $routes = &$routes[$element];
                if (strpos(haystack: $element, needle: '{') === 0) {
                    $param = substr(
                        string: $element,
                        offset: 1,
                        length: strpos(haystack: $element, needle: ':') - 1
                    );
                    $this->sess['uriParams'][$param] = $element;
                }
                continue;
            } else {
                if ((isset($routes['__FILE__']) && count(value: $routes) > 1)
                    || (!isset($routes['__FILE__']) && count(value: $routes) > 0)
                ) {
                    $foundIntRoute = false;
                    $foundIntParamName = false;
                    $foundStringRoute = false;
                    $foundStringParamName = false;
                    foreach (array_keys($routes) as $routeElement) {
                        if (strpos(haystack: $routeElement, needle: '{') === 0) {
                            // Is a dynamic URI element
                            $this->_processRouteElement(
                                routeElement: $routeElement,
                                element: $element,
                                foundIntRoute: $foundIntRoute,
                                foundIntParamName: $foundIntParamName,
                                foundStringRoute: $foundStringRoute,
                                foundStringParamName: $foundStringParamName
                            );
                        }
                    }
                    if ($foundIntRoute) {
                        $configuredUri[] = $foundIntRoute;
                        $this->sess['uriParams'][$foundIntParamName] = (int)$element;
                    } elseif ($foundStringRoute) {
                        $configuredUri[] = $foundStringRoute;
                        $this->sess['uriParams'][$foundStringParamName] = urldecode(
                            string: $element
                        );
                    } else {
                        throw new \Exception(
                            message: 'Route not supported',
                            code: HttpStatus::$BadRequest
                        );
                    }
                    $routes = &$routes[
                        ($foundIntRoute ? $foundIntRoute : $foundStringRoute)
                    ];
                } elseif ($key === $routeLastElementPos
                    && Env::$allowConfigRequest == 1
                    && Env::$configRequestUriKeyword === $element
                ) {
                    $this->isConfigRequest = true;
                    break;
                } else {
                    throw new \Exception(
                        message: 'Route not supported',
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }

        $this->configuredUri = '/' . implode(separator: '/', array: $configuredUri);
        $this->_validateConfigFile(routes: $routes);
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
    private function _processRouteElement(
        $routeElement,
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

        // Check for compulsory values
        $dynamicRoute = trim(string: $routeElement, characters: '{}');
        $mode = 'include';
        $preferredValues = [];
        if (strpos(haystack: $routeElement, needle: '|') !== false) {
            [$dynamicRoute, $preferredValuesString] = explode(
                separator: '|',
                string: $dynamicRoute
            );
            if (strpos(haystack: $preferredValuesString, needle: '!') === 0) {
                $mode = 'exclude';
                $preferredValuesString = substr(
                    string: $preferredValuesString,
                    offset: 1
                );
            }
            $preferredValues = strlen(string: $preferredValuesString) > 0 ?
                explode(separator: ', ', string: $preferredValuesString) : [];
        }

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

        if (count(value: $preferredValues) > 0) {
            switch ($mode) {
            case 'include': // preferred values
                if (!in_array(needle: $element, haystack: $preferredValues)) {
                    throw new \Exception(
                        message: "'{$element}' not allowed in {$routeElement}",
                        code: HttpStatus::$InternalServerError
                    );
                }
                break;
            case 'exclude': // exclude set values
                if (in_array(needle: $element, haystack: $preferredValues)) {
                    throw new \Exception(
                        message: "'{$element}' restricted in config {$routeElement}",
                        code: HttpStatus::$InternalServerError
                    );
                }
                break;
            }
        }

        if ($paramDataType === 'int' && ctype_digit(text: $element)) {
            $foundIntRoute = $routeElement;
            $foundIntParamName = $paramName;
        }
        if ($paramDataType === 'string') {
            $foundStringRoute = $routeElement;
            $foundStringParamName = $paramName;
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
    private function _validateConfigFile(&$routes): void
    {
        // Set route code file
        if (!(isset($routes['__FILE__']) && ($routes['__FILE__'] === false
            || file_exists(filename: $routes['__FILE__'])))
        ) {
            throw new \Exception(
                message: 'Missing config for ' . $this->REQUEST_METHOD . ' method',
                code: HttpStatus::$InternalServerError
            );
        }

        $this->sqlConfigFile = $routes['__FILE__'];
    }
}
