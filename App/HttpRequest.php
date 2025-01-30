<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpResponse;
use Microservices\App\HttpStatus;
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
     * @var string
     */
    public $__file__ = null;

    /**
     * Session detials of a request
     *
     * @var null|array
     */
    public $session = null;

    /**
     * Client details
     *
     * @var null|array
     */
    public $clientDetails = null;

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
     * Cache Keys
     */
    public $tokenKey = null;
    public $clientKey = null;
    public $groupKey = null;
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
     * Load Client Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadClientDetails()
    {
        $this->clientKey = CacheKey::Client($this->HOST);
        if (!$this->cache->cacheExists($this->clientKey)) {
            throw new \Exception("Invalid Host '{$this->HOST}'", HttpStatus::$InternalServerError);
        }

        $this->session['clientDetails'] = json_decode($this->cache->getCache($this->clientKey), true);
    }

    /**
     * Load User Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadUserDetails()
    {
        if (preg_match('/Bearer\s(\S+)/', $this->HTTP_AUTHORIZATION, $matches)) {
            $this->session['token'] = $matches[1];
            $this->tokenKey = CacheKey::Token($this->session['token']);
            if (!$this->cache->cacheExists($this->tokenKey)) {
                throw new \Exception('Token expired', HttpStatus::$BadRequest);
            }
            $this->session['userDetails'] = json_decode($this->cache->getCache($this->tokenKey), true);
        }
        if (empty($this->session['token'])) {
            throw new \Exception('Token missing', HttpStatus::$BadRequest);
        }
    }

    /**
     * Load User Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadGroupDetails()
    {
        // Load groupDetails
        if (empty($this->session['userDetails']['user_id']) || empty($this->session['userDetails']['group_id'])) {
            throw new \Exception('Invalid session', HttpStatus::$InternalServerError);
        }

        $this->groupKey = CacheKey::Group($this->session['userDetails']['group_id']);
        if (!$this->cache->cacheExists($this->groupKey)) {
            throw new \Exception("Cache '{$this->groupKey}' missing", HttpStatus::$InternalServerError);
        }

        $this->session['groupDetails'] = json_decode($this->cache->getCache($this->groupKey), true);
    }

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
            $routeFileLocation = Constants::$DOC_ROOT . '/Config/Routes/' . $this->session['groupDetails']['name'] . '/' . $this->REQUEST_METHOD . 'routes.php';
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
                    Env::$isConfigRequest = true;
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
     * @param array $routes Routes config.
     * @return void
     * @throws \Exception
     */
    private function validateConfigFile(&$routes)
    {
        // Set route code file.
        if (!(isset($routes['__file__']) && ($routes['__file__'] === false || file_exists($routes['__file__'])))) {
            throw new \Exception('Missing route configuration file for ' . $this->REQUEST_METHOD . ' method', HttpStatus::$InternalServerError);
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
            $this->session['payloadType'] = 'Object';
            $this->session['payload'] = !empty($_GET) ? $_GET : [];
        } else {
            // Load Payload
            $this->jsonDecode->indexJSON();
            $this->session['payloadType'] = $this->jsonDecode->jsonType();
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

    /**
     * Init server connection based on $fetchFrom
     *
     * @param string $fetchFrom Master/Slave
     * @return void
     * @throws \Exception
     */
    public function setConnection($fetchFrom)
    {
        if (is_null($this->session['clientDetails'])) {
            throw new \Exception('Yet to set connection params', HttpStatus::$InternalServerError);
        }

        // Set Database credentials
        switch ($fetchFrom) {
            case 'Master':
                $this->setDb(
                    getenv($this->session['clientDetails']['master_db_server_type']),
                    getenv($this->session['clientDetails']['master_db_hostname']),
                    getenv($this->session['clientDetails']['master_db_port']),
                    getenv($this->session['clientDetails']['master_db_username']),
                    getenv($this->session['clientDetails']['master_db_password']),
                    getenv($this->session['clientDetails']['master_db_database'])
                );
                break;
            case 'Slave':
                $this->setDb(
                    getenv($this->session['clientDetails']['slave_db_server_type']),
                    getenv($this->session['clientDetails']['slave_db_hostname']),
                    getenv($this->session['clientDetails']['slave_db_port']),
                    getenv($this->session['clientDetails']['slave_db_username']),
                    getenv($this->session['clientDetails']['slave_db_password']),
                    getenv($this->session['clientDetails']['slave_db_database'])
                );
                break;
            default:
                throw new \Exception("Invalid fetchFrom value '{$fetchFrom}'", HttpStatus::$InternalServerError);
        }
    }
}
