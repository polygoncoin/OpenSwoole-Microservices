<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
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
     * @var string[]
     */
    public $routeElements = [];

    /**
     * Locaton of File containing code for route
     *
     * @var null|string
     */
    public $__file__ = null;

    /**
     * Inputs detials of a request
     *
     * @var null|array
     */
    public $conditions = null;

    /**
     * Client details
     *
     * @var null|array
     */
    public $clientInfo = null;

    /**
     * Group Info
     *
     * @var null|array
     */
    public $groupInfo = null;

    /**
     * Json Decode Object
     *
     * @var null|Cache
     */
    public $cache = null;

    /**
     * Json Decode Object
     *
     * @var null|Database
     */
    public $db = null;

    /**
     * Json Decode Object
     *
     * @var null|JsonDecode
     */
    public $jsonDecode = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $httpRequestDetails = null;

    /**
     * Details var from $httpRequestDetails.
     */
    public $HOST = null;
    public $REQUEST_METHOD = null;
    public $HTTP_AUTHORIZATION = null;
    public $REMOTE_ADDR = null;
    public $ROUTE = null;

    /**
     * ids
     */
    public $userId = null;
    public $groupId = null;

    /**
     * Cache Keys
     */
    public $t_key = null;
    public $c_key = null;
    public $g_key = null;
    public $cidr_key = null;

    /**
     * Payload stream
     */
    private $payloadStream = null;

    /**
     * Constructor
     *
     * @param array $httpRequestDetails
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->httpRequestDetails = &$httpRequestDetails;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->HOST = $this->httpRequestDetails['server']['host'];
        $this->REQUEST_METHOD = $this->httpRequestDetails['server']['request_method'];
        if (isset($this->httpRequestDetails['header']['authorization'])) {
            $this->HTTP_AUTHORIZATION = $this->httpRequestDetails['header']['authorization'];
        }
        $this->REMOTE_ADDR = $this->httpRequestDetails['server']['remote_addr'];
        $this->ROUTE = '/' . trim($this->httpRequestDetails['get'][Constants::$ROUTE_URL_PARAM], '/');

        if (isset($this->httpRequestDetails['post']['Payload'])) {
            $this->payloadStream = fopen("php://memory", "rw+b");
            fwrite($this->payloadStream, $this->httpRequestDetails['post']['Payload']);
            $this->jsonDecode = new JsonDecode($this->payloadStream);
            $this->jsonDecode->init();
        }

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
     * Check Host request
     *
     * @return void
     */
    public function checkHost()
    {
        $this->c_key = "c:{$this->HOST}";
        if (!$this->cache->cacheExists($this->c_key)) {
            throw new \Exception("Invalid Host '{$this->HOST}'", 501);
        }

        $this->clientInfo = json_decode($this->cache->getCache($this->c_key), true);
    }

    /**
     * Loads token from HTTP_AUTHORIZATION
     *
     * @return void
     */
    public function loadToken()
    {
        if (!is_null($this->HTTP_AUTHORIZATION) && preg_match('/Bearer\s(\S+)/', $this->HTTP_AUTHORIZATION, $matches)) {
            $this->conditions['token'] = $matches[1];
            $this->t_key = CacheKey::Token($this->conditions['token']);
            if (!$this->cache->cacheExists($this->t_key)) {
                throw new \Exception('Token expired', 400);
            }
            $this->conditions['readOnlySession'] = json_decode($this->cache->getCache($this->t_key), true);
            $this->userId = $this->conditions['readOnlySession']['user_id'];
            $this->groupId = $this->conditions['readOnlySession']['group_id'];
            $this->checkRemoteIp();
        } else {
            throw new \Exception('Token missing', 400);
        }

        if (empty($this->conditions['token'])) {
            throw new \Exception('Token missing', 400);
        }
    }

    /**
     * Load session with help of token
     *
     * @return void
     */
    public function initSession()
    {
        if (empty($this->conditions['readOnlySession']['user_id']) || empty($this->conditions['readOnlySession']['group_id'])) {
            throw new \Exception('Invalid session', 501);
        }

        $this->g_key = CacheKey::Group($this->groupId);
        if (!$this->cache->cacheExists($this->g_key)) {
            throw new \Exception("Cache '{$this->g_key}' missing", 501);
        }

        $this->groupInfo = json_decode($this->cache->getCache($this->g_key), true);
    }

    /**
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     * @return void
     */
    public function setConnection($fetchFrom)
    {
        if (is_null($this->clientInfo)) {
            throw new \Exception('Yet to set connection params', 501);
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                $this->setDb(
                    getenv($this->clientInfo['master_db_server_type']),
                    getenv($this->clientInfo['master_db_hostname']),
                    getenv($this->clientInfo['master_db_port']),
                    getenv($this->clientInfo['master_db_username']),
                    getenv($this->clientInfo['master_db_password']),
                    getenv($this->clientInfo['master_db_database'])
                );
                break;
            case 'Slave':
                $this->setDb(
                    getenv($this->clientInfo['slave_db_server_type']),
                    getenv($this->clientInfo['slave_db_hostname']),
                    getenv($this->clientInfo['slave_db_port']),
                    getenv($this->clientInfo['slave_db_username']),
                    getenv($this->clientInfo['slave_db_password']),
                    getenv($this->clientInfo['slave_db_database'])
                );
                break;
            default:
                throw new \Exception("Invalid fetchFrom value '{$fetchFrom}'", 501);
        }
    }

    /**
     * Validate request IP
     *
     * @return void
     */
    public function checkRemoteIp()
    {
        $groupId = $this->conditions['readOnlySession']['group_id'];

        $this->cidr_key = CacheKey::CIDR($this->groupId);
        if ($this->cache->cacheExists($this->cidr_key)) {
            $cidrs = json_decode($this->cache->getCache($this->cidr_key), true);
            $ipNumber = ip2long($this->REMOTE_ADDR);
            $isValidIp = false;
            foreach ($cidrs as $cidr) {
                if ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                    $isValidIp = true;
                    break;
                }
            }
            if (!$isValidIp) {
                throw new \Exception('IP not supported', 400);
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
        $Constants = __NAMESPACE__ . '\Constants';
        $Env = __NAMESPACE__ . '\Env';

        if (is_null($routeFileLocation)) {
            $routeFileLocation = Constants::$DOC_ROOT . '/Config/Routes/' . $this->groupInfo['name'] . '/' . $this->REQUEST_METHOD . 'routes.php';
        }

        if (file_exists($routeFileLocation)) {
            $routes = include $routeFileLocation;
        } else {
            throw new \Exception('Missing route file for ' . $this->REQUEST_METHOD . ' method', 501);
        }

        $this->routeElements = explode('/', trim($this->ROUTE, '/'));
        $routeLastElementPos = count($this->routeElements) - 1;
        $configuredUri = [];

        foreach($this->routeElements as $key => $element) {
            $pos = false;
            if (isset($routes[$element])) {
                $configuredUri[] = $element;
                $routes = &$routes[$element];
                if (strpos($element, '{') === 0) {
                    $param = substr($element, 1, strpos($element, ':') - 1);
                    $this->conditions['uriParams'][$param] = $element;
                }
                continue;
            } else {
                if (
                    (isset($routes['__file__']) && count($routes) > 1)
                    || (!isset($routes['__file__']) && count($routes) > 0)
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
                        $this->conditions['uriParams'][$foundIntParamName] = (int)$element;
                    } else if ($foundStringRoute) {
                        $configuredUri[] = $foundStringRoute;
                        $this->conditions['uriParams'][$foundStringParamName] = urldecode($element);
                    } else {
                        throw new \Exception('Route not supported', 400);
                    }
                    $routes = &$routes[(($foundIntRoute) ? $foundIntRoute : $foundStringRoute)];
                } else if (
                    $key === $routeLastElementPos
                    && Env::$allowConfigRequest == 1
                    && Env::$configRequestUriKeyword === $element
                ) {
                    Env::$isConfigRequest = true;
                    break;
                } else {
                    throw new \Exception('Route not supported', 400);
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
            throw new \Exception('Invalid datatype set for Route', 501);
        }

        if (count($preferredValues) > 0) {
            switch ($mode) {
                case 'include': // preferred values
                    if (!in_array($element, $preferredValues)) {
                        throw new \Exception("Element value '{$element}' not allowed in config {$routeElement}", 501);
                    }
                    break;
                case 'exclude': // exclude set values
                    if (in_array($element, $preferredValues)) {
                        throw new \Exception("Element value '{$element}' restricted in config {$routeElement}", 501);
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
     * @param array $routes Routes config.
     * @return void
     */
    private function validateConfigFile(&$routes)
    {
        // Set route code file.
        if (!(isset($routes['__file__']) && ($routes['__file__'] === false || file_exists($routes['__file__'])))) {
            throw new \Exception('Missing route configuration file for ' . $this->REQUEST_METHOD . ' method', 501);
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
        if ($this->REQUEST_METHOD === Constants::$GET) {
            $this->urlDecode($_GET);
            $this->conditions['httprequestPayloadType'] = 'Object';
            $this->conditions['payload'] = !empty($_GET) ? $_GET : [];
        } else {
            // Load Payload
            $this->jsonDecode->indexJSON();
            $this->conditions['httprequestPayloadType'] = $this->jsonDecode->jsonType();
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
    }
}
