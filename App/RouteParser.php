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

use Microservices\App\Constants;
use Microservices\App\Env;
use Microservices\App\HttpRequest;
use Microservices\App\HttpStatus;

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
     * Raw route / Configured Uri
     *
     * @var string
     */
    public $configuredUri = '';

    /**
     * Location of File containing code for route
     *
     * @var string
     */
    public $sqlConfigFile = null;

    /**
     * Rate Limiter
     *
     * @var null|HttpRequest
     */
    private $_req = null;

    /**
     * Session reference variable
     *
     * @var null|array
     */
    private $_s = null;

    /**
     * Constructor
     *
     * @param HttpRequest $req HTTP Request object
     */
    public function __construct(&$req)
    {
        $this->_req = &$req;
        $this->_s = &$this->_req->s;
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
            if ($this->_req->open) {
                $routeFileLocation = Constants::$PUBLIC_HTML .
                    DIRECTORY_SEPARATOR . 'Config' .
                    DIRECTORY_SEPARATOR . 'Routes' .
                    DIRECTORY_SEPARATOR . 'Open' .
                    DIRECTORY_SEPARATOR . $this->_req->METHOD . 'routes.php';
            } else {
                $routeFileLocation = Constants::$PUBLIC_HTML .
                    DIRECTORY_SEPARATOR . 'Config' .
                    DIRECTORY_SEPARATOR . 'Routes' .
                    DIRECTORY_SEPARATOR . 'Auth' .
                    DIRECTORY_SEPARATOR . 'ClientDB' .
                    DIRECTORY_SEPARATOR . 'Groups' .
                    DIRECTORY_SEPARATOR . $this->_s['gDetails']['name'] .
                    DIRECTORY_SEPARATOR . $this->_req->METHOD . 'routes.php';
            }
        }

        if (file_exists(filename: $routeFileLocation)) {
            $routes = include $routeFileLocation;
        } else {
            throw new \Exception(
                message: 'Route file missing: ' . $this->_req->METHOD . ' method',
                code: HttpStatus::$InternalServerError
            );
        }

        $this->routeElements = explode(
            separator: '/',
            string: trim(string: $this->_req->ROUTE, characters: '/')
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
                    $this->_s['uriParams'][$param] = $element;
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
                    foreach (array_keys(array: $routes) as $routeElement) {
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
                        $this->_s['uriParams'][$foundIntParamName]
                            = (int)$element;
                    } elseif ($foundStringRoute) {
                        $configuredUri[] = $foundStringRoute;
                        $this->_s['uriParams'][$foundStringParamName]
                            = urldecode(string: $element);
                    } else {
                        throw new \Exception(
                            message: 'Route not supported',
                            code: HttpStatus::$BadRequest
                        );
                    }
                    $routes = &$routes[
                        ($foundIntRoute ?? $foundStringRoute)
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
                if (isset($routes['iRepresentation'])
                    && Env::isValidDataRep(
                        dataRepresentation: $routes['iRepresentation']
                    )
                ) {
                    Env::$iRepresentation = $routes['iRepresentation'];
                }
            }
        }

        // Input data representation over rides global and routes settings
        // Switch Input data representation if set in URL param
        if (Env::$allowGetRepresentation == 1
            && isset($this->_req->http['get']['iRepresentation'])
            && Env::isValidDataRep(
                dataRepresentation: $this->_req->http['get']['iRepresentation']
            )
        ) {
            Env::$iRepresentation = $this->_req->http['get']['iRepresentation'];
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
                message: 'Missing config for ' . $this->_req->METHOD . ' method',
                code: HttpStatus::$InternalServerError
            );
        }

        if (!empty($routes['__FILE__'])
            && file_exists(filename: $routes['__FILE__'])
        ) {
            $this->sqlConfigFile = $routes['__FILE__'];

            // Output data representation over rides global
            // Output data representation set in Query config file
            $sqlConfig = include $this->sqlConfigFile;
            if (isset($sqlConfig['oRepresentation'])
                && Env::isValidDataRep(
                    dataRepresentation: $sqlConfig['oRepresentation']
                )
            ) {
                Env::$oRepresentation = $sqlConfig['oRepresentation'];
            }
        }

        // Switch Output data representation if set in URL param
        if (Env::$allowGetRepresentation == 1
            && isset($this->_req->http['get']['oRepresentation'])
            && Env::isValidDataRep(
                dataRepresentation: $this->_req->http['get']['oRepresentation']
            )
        ) {
            Env::$oRepresentation = $this->_req->http['get']['oRepresentation'];
        }
    }
}
