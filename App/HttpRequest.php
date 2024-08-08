<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpResponse;
use Microservices\App\JsonDecode;

use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

/*
 * Class handling details of HTTP request
 *
 * This class is built to process and handle HTTP request
 *
 * @category   HTTP Request
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class HttpRequest
{
    /**
     * Raw route / Configured Uri
     *
     * @var string
     */
    public $configuredUri = '';

    /**
     * Array containing details of received route elements
     *
     * @var array
     */
    public $routeElements = [];

    /**
     * Locaton of File containing code for route
     *
     * @var string
     */
    public $__file__ = null;

    /**
     * Inputs detials of a request
     *
     * @var array
     */
    public $input = null;

    /**
     * Logged-in User ID
     *
     * @var integer
     */
    public $userId = null;

    /**
     * Logged-in user Group ID
     *
     * @var integer
     */
    public $groupId = null;
    
    /**
     * Json Decode Object
     *
     * @var Microservices\App\Servers\Cache\Cache
     */
    public $cache = null;

    /**
     * Json Decode Object
     *
     * @var Microservices\App\Servers\Database\Database
     */
    public $db = null;

    /**
     * Json Decode Object
     *
     * @var Microservices\App\JsonDecode
     */
    public $jsonDecode = null;

    /**
     * Microservices HTTP Response
     * 
     * @var Microservices\App\HttpResponse
     */
    public $httpResponse = null;

    /**
     * OpenSwoole Http Request
     * 
     * @var OpenSwoole\Http\Request
     */
    private $request = null;

    /**
     * OpenSwoole Http Response
     * 
     * @var OpenSwoole\Http\Response
     */
    private $response = null;

    /**
     * Details var from $request.
     */
    public $REQUEST_METHOD = null;
    public $HTTP_AUTHORIZATION = null;
    public $REMOTE_ADDR = null;
    public $ROUTE = null;

    /**
     * Constructor
     */
    public function __construct(&$httpResponse)
    {
        $this->httpResponse = $httpResponse;
    }
    
    /**
     * Initialize
     *
     * @param OpenSwoole\Http\Request  $request
     * @param OpenSwoole\Http\Response $response
     * @return boolean
     */
    public function init(Request &$request, Response &$response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->REQUEST_METHOD = $this->request->server['request_method'];
        if (isset($this->request->header['authorization'])) {
            $this->HTTP_AUTHORIZATION = $this->request->header['authorization'];
        }
        $this->REMOTE_ADDR = $this->request->server['remote_addr'];
        $this->ROUTE = '/' . trim($this->request->get[Constants::$ROUTE_URL_PARAM], '/');
        
        $this->jsonDecode = new JsonDecode();
        $this->jsonDecode->init($request, $response);

        Env::$cacheType = getenv('cacheType');
        Env::$cacheHostname = getenv('cacheHostname');
        Env::$cachePort = getenv('cachePort');
        Env::$cacheUsername = getenv('cacheUsername');
        Env::$cachePassword = getenv('cachePassword');
        Env::$cacheDatabase = getenv('cacheDatabase');

        $this->setCache();
    }

    /**
     * Set Cache
     *
     * @return void
     */
    public function setCache()
    {
        $cacheNS = 'Microservices\\App\\Servers\\Cache\\'.Env::$cacheType;
        $this->cache = new $cacheNS(
            Env::$cacheHostname,
            Env::$cachePort,
            Env::$cacheUsername,
            Env::$cachePassword,
            Env::$cacheDatabase
        );
    }

    /**
     * Set DB
     *
     * @return void
     */
    public function setDb()
    {
        $dbNS = 'Microservices\\App\\Servers\\Database\\'.Env::$dbType;
        $this->db = new $dbNS(
            Env::$dbHostname,
            Env::$dbPort,
            Env::$dbUsername,
            Env::$dbPassword,
            Env::$dbDatabase
        );
    }

    /**
     * Loads token from HTTP_AUTHORIZATION
     *
     * @return void
     */
    public function loadToken()
    {
        if (!is_null($this->HTTP_AUTHORIZATION) && preg_match('/Bearer\s(\S+)/', $this->HTTP_AUTHORIZATION, $matches)) {
            $this->input['token'] = $matches[1];
            $token = $this->input['token'];
            if (!$this->cache->cacheExists($token)) {
                $this->httpResponse->return5xx(501, 'Token expired');
                return;
            }
            $this->input['readOnlySession'] = json_decode($this->cache->getCache($token), true);
            $this->userId = $this->input['readOnlySession']['user_id'];
            $this->groupId = $this->input['readOnlySession']['group_id'];    
            $this->checkRemoteIp();
        } else {
            $this->httpResponse->return4xx(404, 'Missing token in authorization header');
            return;
        }

        if (empty($this->input['token'])) {
            $this->httpResponse->return4xx(404, 'Missing token');
            return;
        }
    }

    /**
     * Load session with help of token
     *
     * @return void
     */
    public function initSession()
    {
        if (empty($this->input['readOnlySession']['user_id']) || empty($this->input['readOnlySession']['group_id'])) {
            $this->httpResponse->return4xx(404, 'Invalid session');
            return;
        }

        $key = 'group:'.$this->groupId;
        if (!$this->cache->cacheExists($key)) {
            $this->httpResponse->return5xx(501, "Cache '{$key}' missing.");
            return;
        }

        $groupInfoArr = json_decode($this->cache->getCache($key), true);

        // Set Database credentials
        Env::$dbType = getenv($groupInfoArr['db_server_type']);
        Env::$dbHostname = getenv($groupInfoArr['db_hostname']);
        Env::$dbPort = getenv($groupInfoArr['db_port']);
        Env::$dbUsername = getenv($groupInfoArr['db_username']);
        Env::$dbPassword = getenv($groupInfoArr['db_password']);
        Env::$dbDatabase = getenv($groupInfoArr['db_database']);

        $this->setDb();
    }

    /**
     * Validate request IP
     *
     * @return void
     */
    public function checkRemoteIp()
    {
        $groupId = $this->input['readOnlySession']['group_id'];

        $key = 'cidr:'.$this->groupId;
        if ($this->cache->cacheExists($key)) {
            $cidrs = json_decode($this->cache->getCache($key), true);
            $ipNumber = ip2long($this->REMOTE_ADDR);
            $isValidIp = false;
            foreach ($cidrs as $cidr) {
                if ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                    $isValidIp = true;
                    break;
                }
            }
            if (!$isValidIp) {
                $this->httpResponse->return4xx(404, 'IP not supported');
                return;
            }
        }
    }

    /**
     * Parse route as per method
     *
     * @param string $routeFileLocation Route file
     * @return void
     */
    public function parseRoute($routeFileLocation = null)
    {
        if (is_null($routeFileLocation)) {
            $routeFileLocation = Constants::$DOC_ROOT . '/Config/Routes/' . $this->input['readOnlySession']['group_name'] . '/' . $this->REQUEST_METHOD . 'routes.php';
        }

        if (file_exists($routeFileLocation)) {
            $routes = include $routeFileLocation;
        } else {
            $this->httpResponse->return5xx(501, 'Missing route file for ' . $this->REQUEST_METHOD . ' method');
            return;
        }

        $this->routeElements = explode('/', trim($this->ROUTE, '/'));
        $routeLastElementPos = count($this->routeElements) - 1;
        Env::$isConfigRequest = ($this->routeElements[$routeLastElementPos]) === 'config';
        $configuredUri = [];

        foreach($this->routeElements as $key => $element) {
            $pos = false;
            if (isset($routes[$element])) {
                if (
                    Env::$allowConfigRequest == 1 &&
                    Env::$isConfigRequest && 
                    $routes[$element] === true
                ) {
                    break;
                }
                $configuredUri[] = $element;
                $routes = &$routes[$element];
                if (strpos($element, '{') === 0) {
                    $param = substr($element, 1, strpos($element, ':') - 1);
                    $this->input['uriParams'][$param] = $element;
                }
                continue;
            } else {
                if (is_array($routes)) {
                    $foundIntRoute = false;
                    $foundStringRoute = false;
                    foreach (array_keys($routes) as $routeElement) {
                        if (strpos($routeElement, '{') === 0) {// Is a dynamic URI element
                            $paramName = $this->processRouteElement($routeElement, $element, $foundIntRoute, $foundStringRoute);
                        }
                    }
                    if ($foundIntRoute) {
                        $configuredUri[] = $foundIntRoute;
                        $this->input['uriParams'][$paramName] = (int)$element;
                    } else if ($foundStringRoute) {
                        $configuredUri[] = $foundStringRoute;
                        $this->input['uriParams'][$paramName] = urldecode($element);
                    } else {
                        $this->httpResponse->return4xx(404, 'Route not supported');
                        return;
                    }
                } else {
                    $this->httpResponse->return4xx(404, 'Route not supported');
                    return;
                }
                $routes = &$routes[(($foundIntRoute) ? $foundIntRoute : $foundStringRoute)];
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
     */
    private function processRouteElement($routeElement, &$element, &$foundIntRoute, &$foundStringRoute)
    {
        // Is a dynamic URI element
        if (strpos($routeElement, '{') !== 0) {
            return false;
        }

        // Check for compulsary values
        $dynamicRoute = trim($routeElement, '{}');
        $preferredValues = [];
        if (strpos($routeElement, '|') !== false) {
            list($dynamicRoute, $preferredValuesString) = explode('|', $dynamicRoute);
            $preferredValues = ((strlen($preferredValuesString) > 0) ? explode(',', $preferredValuesString) : []);
        }

        list($paramName, $paramDataType) = explode(':', $dynamicRoute);
        if (!in_array($paramDataType, ['int','string'])) {
            $this->httpResponse->return5xx(501, 'Invalid datatype set for Route');
            return;
        }

        if (count($preferredValues) > 0 && !in_array($element, $preferredValues)) {
            $this->httpResponse->return4xx(404, $routeElement);
            return;
        }

        if ($paramDataType === 'int') {
            if (!ctype_digit($element)) {
                $this->httpResponse->return4xx(404, "Invalid {$paramName}");
                return;
            } else {
                $foundIntRoute = $routeElement;
            }
        } else {
            $foundStringRoute = $routeElement;
        }

        return $paramName;
    }

    /**
     * Validate config file
     *
     * @param array $routes Routes config.
     * @return void
     */
    private function validateConfigFile(&$routes)
    {
        // Set route code file.
        if (!(isset($routes['__file__']) && ($routes['__file__'] === false || file_exists($routes['__file__'])))) {
            $this->httpResponse->return5xx(501, 'Missing route configuration file for ' . $this->REQUEST_METHOD . ' method');
            return;
        }

        $this->__file__ = $routes['__file__'];
    }

    /**
     * Loads request payoad
     *
     * @return void
     */
    public function loadPayload()
    {
        $payloadArr = [];

        if ($this->REQUEST_METHOD === Constants::$GET) {
            $this->urlDecode($_GET);
            $payloadArr = !empty($_GET) ? $_GET : [];
            $this->input['payloadType'] = 'Object';
            $this->input['payloadArr'] = $payloadArr;
        } else {
            // Load Payload
            $this->jsonDecode->validate();
            $this->jsonDecode->indexJSON();
            $this->input['payloadType'] = $this->jsonDecode->keysType();
        }
    }

    /**
     * Function to find payload is an object/array
     *
     * @param array $arr Array vales to be decoded. Basically $_GET.
     * @return void
     */
    public function urlDecode(&$arr)
    {
        if (is_array($arr)) {
            foreach ($arr as $key => &$value) {
                if (is_array($value)) {
                    $this->urlDecode($value);
                } else {
                    $decodedVal = urldecode($value);
                    $array = json_decode($decodedVal, true);
                    if (!is_null($array)) {
                        $value = $array;
                    } else {
                        $value = $decodedVal;
                    }
                }
            }
        } else {
            $decodedVal = urldecode($arr);
            $array = json_decode($decodedVal, true);
            if (!is_null($array)) {
                $arr = $array;
            } else {
                $arr = $decodedVal;
            }
        }
    }

    /**
     * Returns Start IP and End IP for a given CIDR
     *
     * @param  string $cidrs IP address range in CIDR notation for check
     * @return array
     */
    public function cidrsIpNumber($cidrs)
    {
        $response = [];

        foreach (explode(',', str_replace(' ', '', $cidrs)) as $cidr) {
            if (strpos($cidr, '/')) {
                list($cidrIp, $bits) = explode('/', str_replace(' ', '', $cidr));
                $binCidrIpStr = str_pad(decbin(ip2long($cidrIp)), 32, 0, STR_PAD_LEFT);
                $startIpNumber = bindec(str_pad(substr($binCidrIpStr, 0, $bits), 32, 0, STR_PAD_RIGHT));
                $endIpNumber = $startIpNumber + pow(2, $bits) - 1;
                $response[] = [
                    'start' => $startIpNumber,
                    'end' => $endIpNumber
                ];
            } else {
                if ($ipNumber = ip2long($cidr)) {
                    $response[] = [
                        'start' => $ipNumber,
                        'end' => $ipNumber
                    ];    
                }
            }
        }

        return $response;
    }
}
