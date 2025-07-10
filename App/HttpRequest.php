<?php
/**
 * HTTP Request
 * php version 8.3
 *
 * @category  HTTP_Request
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\DataRepresentation\AbstractDataDecode;
use Microservices\App\DataRepresentation\DataDecode;
use Microservices\App\Gateway;
use Microservices\App\HttpStatus;
use Microservices\App\Middleware\Auth;
use Microservices\App\Servers\Cache\AbstractCache;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * HTTP Request
 * php version 8.3
 *
 * @category  HTTP_Request
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpRequest extends Gateway
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
     * Is a config request flag
     *
     * @var bool
     */
    public $isConfigRequest = false;

    /**
     * Location of File containing code for route
     *
     * @var string
     */
    public $sqlConfigFile = null;

    /**
     * Pre / Post hooks defined in respective Route file
     *
     * @var string
     */
    public $routeHook = null;

    /**
     * Session details of a request
     *
     * @var null|array
     */
    public $sess = null;

    /**
     * Client Id
     *
     * @var null|int
     */
    public $clientId = null;

    /**
     * Group Id
     *
     * @var null|int
     */
    public $groupId = null;

    /**
     * User Id
     *
     * @var null|int
     */
    public $userId = null;

    /**
     * Cache Object
     *
     * @var null|AbstractCache
     */
    public $cache = null;

    /**
     * SQL Cache Object
     *
     * @var null|AbstractCache
     */
    public $sqlCache = null;

    /**
     * Auth middleware Object
     *
     * @var null|Auth
     */
    public $auth = null;

    /**
     * Database Object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Json Decode Object
     *
     * @var null|AbstractDataDecode
     */
    public $dataDecode = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $http = null;

    /**
     * Open To World Request
     *
     * @var null|bool
     */
    public $open = null;

    /**
     * Details variable from $http
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
    public $cidrKey = null;
    public $cidrChecked = false;

    /**
     * Client Info
     *
     * @var null|array
     */
    public $clientDetails = null;

    /**
     * Group Info
     *
     * @var null|array
     */
    public $groupDetails = null;

    /**
     * User Info
     *
     * @var null|array
     */
    public $userDetails = null;

    /**
     * Payload stream
     */
    public $payloadStream = null;

    /**
     * Constructor
     *
     * @param array $http HTTP request details
     */
    public function __construct(&$http)
    {
        $this->http = &$http;

        $this->HOST = $this->http['server']['host'];
        $this->REQUEST_METHOD = $this->http['server']['request_method'];
        $this->REMOTE_ADDR = $this->http['server']['remote_addr'];
        $this->ROUTE = '/' . trim(
            string: $this->http['get'][Constants::$ROUTE_URL_PARAM],
            characters: '/'
        );

        if (isset($this->http['header'])
            && isset($this->http['header']['authorization'])
        ) {
            $this->HTTP_AUTHORIZATION = $this->http['header']['authorization'];
            $this->open = false;
        } elseif ($this->ROUTE === '/login') {
            $this->open = false;
        } else {
            $this->open = true;
        }
        if (!$this->open) {
            $this->auth = new Auth(req: $this);
        }
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        return true;
    }

    /**
     * Load Client Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadClientDetails(): void
    {
        if (!is_null(value: $this->clientDetails)) {
            return;
        }

        $this->_loadCache();

        if ($this->open) {
            $this->clientKey = CacheKey::clientOpenToWeb(hostname: $this->HOST);
        } else {
            $this->clientKey = CacheKey::client(hostname: $this->HOST);
        }
        if (!$this->cache->cacheExists(key: $this->clientKey)) {
            throw new \Exception(
                message: "Invalid Host '{$this->HOST}'",
                code: HttpStatus::$InternalServerError
            );
        }

        $this->clientDetails = json_decode(
            json: $this->cache->getCache(
                key: $this->clientKey
            ),
            associative: true
        );
        $this->clientId = $this->clientDetails['client_id'];

        $this->sess['clientDetails'] = &$this->clientDetails;
    }

    /**
     * Loads request payload
     *
     * @return void
     */
    public function loadPayload(): void
    {
        if (isset($this->sess['payloadType'])) {
            return;
        }

        if ($this->REQUEST_METHOD === Constants::$GET) {
            $this->urlDecode(arr: $_GET);
            $this->sess['payloadType'] = 'Object';
            $this->sess['payload'] = !empty($_GET) ? $_GET : [];
        } else {
            if (empty($this->http['post']['Payload'])) {
                $this->http['post']['Payload'] = '{}';
            }

            if (Env::$inputRepresentation === 'Xml') {
                $xml = simplexml_load_string(data: $this->http['post']['Payload']);
                $array = json_decode(
                    json: json_encode(value: $xml),
                    associative: true
                );
                unset($xml);

                $result = [];
                $this->_formatXmlArray(array: $array, result: $result);
                $this->http['post']['Payload'] = json_encode(value: $result);
                $array = null;
                $result = null;
            }

            $this->payloadStream = fopen(filename: "php://memory", mode: "rw+b");
            fwrite(
                stream: $this->payloadStream,
                data: $this->http['post']['Payload']
            );

            $this->dataDecode = new DataDecode(
                dataFileHandle: $this->payloadStream
            );
            $this->dataDecode->init();

            rewind(stream: $this->payloadStream);
            $this->dataDecode->indexData();
            $this->sess['payloadType'] = $this->dataDecode->dataType();
        }
    }

    /**
     * Format Array generated by Xml
     *
     * @param array $array  Array generated by Xml
     * @param array $result Formatted array
     *
     * @return void
     */
    private function _formatXmlArray(&$array, &$result): void
    {
        if (isset($array['Rows']) && is_array(value: $array['Rows'])) {
            $array = &$array['Rows'];
        }

        if (isset($array['Row']) && is_array(value: $array['Row'])) {
            $array = &$array['Row'];
        }

        if (isset($array[0])
            && is_array(value: $array[0]) && count(value: $array) === 1
        ) {
            $array = &$array[0];
            if (empty($array)) {
                return;
            }
        }

        if (!is_array(value: $array)) {
            return;
        }

        foreach ($array as $key => &$value) {
            if ($key === 'attribute') {
                foreach ($value as $k => $v) {
                    $result[$k] = $v;
                }
                continue;
            }
            if (is_array(value: $value)) {
                $result[$key] = [];
                $this->_formatXmlArray(array: $value, result: $result[$key]);
                continue;
            }
            $result[$key] = $value;
        }
    }

    /**
     * Function to find payload is an object/array
     *
     * @param array $arr Array vales to be decoded. Basically $_GET
     *
     * @return void
     */
    public function urlDecode(&$arr): void
    {
        if (is_array(value: $arr)) {
            foreach ($arr as &$value) {
                if (is_array(value: $value)) {
                    $this->urlDecode(arr: $value);
                } else {
                    $decodedVal = urldecode(string: $value);
                    $array = json_decode(json: $decodedVal, associative: true);
                    $value = (!is_null(value: $array)) ? $array : $decodedVal;
                }
            }
        } else {
            $decodedVal = urldecode(string: $arr);
            $array = json_decode(json: $decodedVal, associative: true);
            $arr = (!is_null(value: $array)) ? $array : $decodedVal;
        }
    }

    /**
     * Load cache server
     *
     * @return void
     */
    private function _loadCache(): void
    {
        if (!is_null(value: $this->cache)) {
            return;
        }

        $this->cache = $this->connectCache(
            cacheType: getenv(name: 'cacheType'),
            cacheHostname: getenv(name: 'cacheHostname'),
            cachePort: getenv(name: 'cachePort'),
            cacheUsername: getenv(name: 'cacheUsername'),
            cachePassword: getenv(name: 'cachePassword'),
            cacheDatabase: getenv(name: 'cacheDatabase')
        );
    }
}
