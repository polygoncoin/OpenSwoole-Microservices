<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/*
 * Class handling Route Parsing functionality
 *
 * @category   Route Parser
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class RouteParser extends DbFunctions
{
    /**
     * Parse route as per method
     *
     * @param string $routeFileLocation Route file
     * @return void
     * @throws \Exception
     */
    public function parseRoute($routeFileLocation = null)
    {
        $Constants = __NAMESPACE__ . '\Constants';
        $Env = __NAMESPACE__ . '\Env';

        if (is_null($routeFileLocation)) {
            if ($this->open) {
                $routeFileLocation = Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'Open' . DIRECTORY_SEPARATOR . $this->REQUEST_METHOD . 'routes.php';
            } else {
                $routeFileLocation = Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . $this->session['groupDetails']['name'] . DIRECTORY_SEPARATOR . $this->REQUEST_METHOD . 'routes.php';
            }
        }

        if (file_exists($routeFileLocation)) {
            $routes = include $routeFileLocation;
        } else {
            throw new \Exception('Missing route file for ' . $this->REQUEST_METHOD . ' method', HttpStatus::$InternalServerError);
        }

        $this->routeElements = explode('/', trim($this->ROUTE, '/'));
        $routeLastElementPos = count($this->routeElements) - 1;
        $configuredUri = [];

        foreach($this->routeElements as $key => $element) {
            if (in_array($key, ['__PRE-ROUTE-HOOKS__', '__POST-ROUTE-HOOKS__'])) {
                $this->routeHook[$key] = $element;
                continue;
            }
            $pos = false;
            if (isset($routes[$element])) {
                $configuredUri[] = $element;
                $routes = &$routes[$element];
                if (strpos($element, '{') === 0) {
                    $param = substr($element, 1, strpos($element, ':') - 1);
                    $this->session['uriParams'][$param] = $element;
                }
                continue;
            } else {
                if (
                    (isset($routes['__FILE__']) && count($routes) > 1)
                    || (!isset($routes['__FILE__']) && count($routes) > 0)
                ) {
                    $foundIntRoute = false;
                    $foundIntParamName = false;
                    $foundStringRoute = false;
                    $foundStringParamName = false;
                    foreach (array_keys($routes) as $routeElement) {
                        if (strpos($routeElement, '{') === 0) {// Is a dynamic URI element
                            $this->processRouteElement($routeElement, $element, $foundIntRoute, $foundIntParamName, $foundStringRoute, $foundStringParamName);
                        }
                    }
                    if ($foundIntRoute) {
                        $configuredUri[] = $foundIntRoute;
                        $this->session['uriParams'][$foundIntParamName] = (int)$element;
                    } else if ($foundStringRoute) {
                        $configuredUri[] = $foundStringRoute;
                        $this->session['uriParams'][$foundStringParamName] = urldecode($element);
                    } else {
                        throw new \Exception('Route not supported', HttpStatus::$BadRequest);
                    }
                    $routes = &$routes[(($foundIntRoute) ? $foundIntRoute : $foundStringRoute)];
                } else if (
                    $key === $routeLastElementPos
                    && Env::$allowConfigRequest == 1
                    && Env::$configRequestUriKeyword === $element
                ) {
                    $this->isConfigRequest = true;
                    break;
                } else {
                    throw new \Exception('Route not supported', HttpStatus::$BadRequest);
                }
            }
        }

        $this->configuredUri = '/' . implode('/', $configuredUri);
        $this->validateConfigFile($routes);
    }

    /**
     * Process Route Element
     *
     * @param string $routeElement     Configured route element
     * @param string $element          Element
     * @param string $foundIntRoute    Found as Integer route element
     * @param string $foundStringRoute Found as String route element
     * @return string
     * @throws \Exception
     */
    private function processRouteElement($routeElement, &$element, &$foundIntRoute, &$foundIntParamName, &$foundStringRoute, &$foundStringParamName)
    {
        // Is a dynamic URI element
        if (strpos($routeElement, '{') !== 0) {
            return false;
        }

        // Check for compulsary values
        $dynamicRoute = trim($routeElement, '{}');
        $mode = 'include';
        $preferredValues = [];
        if (strpos($routeElement, '|') !== false) {
            list($dynamicRoute, $preferredValuesString) = explode('|', $dynamicRoute);
            if (strpos($preferredValuesString, '!') === 0) {
                $mode = 'exclude';
                $preferredValuesString = substr($preferredValuesString, 1);
            }
            $preferredValues = ((strlen($preferredValuesString) > 0) ? explode(',', $preferredValuesString) : []);
        }

        list($paramName, $paramDataType) = explode(':', $dynamicRoute);
        if (!in_array($paramDataType, ['int','string'])) {
            throw new \Exception('Invalid datatype set for Route', HttpStatus::$InternalServerError);
        }

        if (count($preferredValues) > 0) {
            switch ($mode) {
                case 'include': // preferred values
                    if (!in_array($element, $preferredValues)) {
                        throw new \Exception("Element value '{$element}' not allowed in config {$routeElement}", HttpStatus::$InternalServerError);
                    }
                    break;
                case 'exclude': // exclude set values
                    if (in_array($element, $preferredValues)) {
                        throw new \Exception("Element value '{$element}' restricted in config {$routeElement}", HttpStatus::$InternalServerError);
                    }
                    break;
            }
        }

        if ($paramDataType === 'int' && ctype_digit($element)) {
            $foundIntRoute = $routeElement;
            $foundIntParamName = $paramName;
        }
        if ($paramDataType === 'string') {
            $foundStringRoute = $routeElement;
            $foundStringParamName = $paramName;
        }
    }

    /**
     * Validate config file
     *
     * @param array $routes Routes config
     * @return void
     * @throws \Exception
     */
    private function validateConfigFile(&$routes)
    {
        // Set route code file
        if (!(isset($routes['__FILE__']) && ($routes['__FILE__'] === false || file_exists($routes['__FILE__'])))) {
            throw new \Exception('Missing route configuration file for ' . $this->REQUEST_METHOD . ' method', HttpStatus::$InternalServerError);
        }

        $this->__FILE__ = $routes['__FILE__'];
    }
}
