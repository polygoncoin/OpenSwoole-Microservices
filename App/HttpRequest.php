<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpResponse;
use Microservices\App\JsonDecode;

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
     * Details var from $request.
     */
    public $REQUEST_METHOD = null;
    public $HTTP_AUTHORIZATION = null;
    public $REMOTE_ADDR = null;
    public $ROUTE = null;

    /**
     * Global DB
     */
    public $globalDB = null;

    /**
     * Global DB
     */
    public $clientDB = null;
    
    /**
     * Microservices Request Details
     * 
     * @var array
     */
    public $inputs = null;

    /**
     * Constructor
     *
     * @param array $inputs
     */
    public function __construct(&$inputs)
    {
        $this->inputs = &$inputs;
        $this->globalDB = Env::$globalDatabase;
    }
    
    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->REQUEST_METHOD = $this->inputs['server']['request_method'];
        if (isset($this->inputs['header']['authorization'])) {
            $this->HTTP_AUTHORIZATION = $this->inputs['header']['authorization'];
        }
        $this->REMOTE_ADDR = $this->inputs['server']['remote_addr'];
        $this->ROUTE = '/' . trim($this->inputs['get'][Constants::$ROUTE_URL_PARAM], '/');
        
        $this->jsonDecode = new JsonDecode($this->inputs);
        $this->jsonDecode->init();

        $this->setCache(
            getenv('cacheType'),
            getenv('cacheHostname'),
            getenv('cachePort'),
            getenv('cacheUsername'),
            getenv('cachePassword'),
            getenv('cacheDatabase')
        );
    }

    /**
     * Set Cache
     *
     * @return void
     */
    public function setCache(
        $cacheType,
        $cacheHostname,
        $cachePort,
        $cacheUsername,
        $cachePassword,
        $cacheDatabase
    )
    {
        $cacheNS = 'Microservices\\App\\Servers\\Cache\\'.$cacheType;
        $this->cache = new $cacheNS(
            $cacheHostname,
            $cachePort,
            $cacheUsername,
            $cachePassword,
            $cacheDatabase
        );
    }

    /**
     * Set DB
     *
     * @return void
     */
    public function setDb(
        $dbType,
        $dbHostname,
        $dbPort,
        $dbUsername,
        $dbPassword,
        $dbDatabase
    )
    {
        $dbNS = 'Microservices\\App\\Servers\\Database\\'.$dbType;
        $this->db = new $dbNS(
            $dbHostname,
            $dbPort,
            $dbUsername,
            $dbPassword,
            $dbDatabase
        );
        $this->clientDB = $this->db->database;
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
                throw new \Exception('Token expired');
            }
            $this->input['readOnlySession'] = json_decode($this->cache->getCache($token), true);
            $this->userId = $this->input['readOnlySession']['user_id'];
            $this->groupId = $this->input['readOnlySession']['group_id'];    
            $this->checkRemoteIp();
        } else {
            throw new \Exception('Token missing');
        }

        if (empty($this->input['token'])) {
            throw new \Exception('Token missing');
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
            throw new \Exception('Invalid session');
        }

        $key = 'group:'.$this->groupId;
        if (!$this->cache->cacheExists($key)) {
            throw new \Exception("Cache '{$key}' missing.");
        }

        $groupInfoArr = json_decode($this->cache->getCache($key), true);

        // Set Database credentials
        if ($this->REQUEST_METHOD === 'GET') {
            $this->setDb(
                getenv($groupInfoArr['read_db_server_type']),
                getenv($groupInfoArr['read_db_hostname']),
                getenv($groupInfoArr['read_db_port']),
                getenv($groupInfoArr['read_db_username']),
                getenv($groupInfoArr['read_db_password']),
                getenv($groupInfoArr['read_db_database'])
            );    
        } else {
            $this->setDb(
                getenv($groupInfoArr['write_db_server_type']),
                getenv($groupInfoArr['write_db_hostname']),
                getenv($groupInfoArr['write_db_port']),
                getenv($groupInfoArr['write_db_username']),
                getenv($groupInfoArr['write_db_password']),
                getenv($groupInfoArr['write_db_database'])
            );
        }
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
                throw new \Exception('IP not supported');
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
            throw new \Exception('Missing route file for ' . $this->REQUEST_METHOD . ' method');
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
                        throw new \Exception('Route not supported');
                    }
                } else {
                    throw new \Exception('Route not supported');
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
            throw new \Exception('Invalid datatype set for Route');
        }

        if (count($preferredValues) > 0 && !in_array($element, $preferredValues)) {
            throw new \Exception($routeElement);
        }

        if ($paramDataType === 'int') {
            if (!ctype_digit($element)) {
                throw new \Exception("Invalid {$paramName}");
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
            throw new \Exception('Missing route configuration file for ' . $this->REQUEST_METHOD . ' method');
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
