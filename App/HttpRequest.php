<?php
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
     * @var boolean
     */
    public $isConfigRequest = false;

    /**
     * Locaton of File containing code for route
     *
     * @var string
     */
    public $__FILE__ = null;

    /**
     * Pre / Post hooks defined in respective Route file
     *
     * @var string
     */
    public $routeHook = null;

    /**
     * Session detials of a request
     *
     * @var null|array
     */
    public $session = null;

    /** @var null|integer */
    public $clientId = null;

    /** @var null|integer */
    public $groupId = null;

    /** @var null|integer */
    public $userId = null;

    /**
     * @var null|AbstractCache
     */
    public $cache = null;

    /**
     * @var null|AbstractCache
     */
    public $sqlCache = null;

    /**
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
    public $httpRequestDetails = null;

    /**
     * Open To World Request
     *
     * @var null|boolean
     */
    public $open = null;

    /**
     * Details var from $httpRequestDetails
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
     * @param array $httpRequestDetails
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->httpRequestDetails = &$httpRequestDetails;

        $this->HOST = $this->httpRequestDetails['server']['host'];
        $this->REQUEST_METHOD = $this->httpRequestDetails['server']['request_method'];
        $this->REMOTE_ADDR = $this->httpRequestDetails['server']['remote_addr'];
        $this->ROUTE = '/' . trim($this->httpRequestDetails['get'][Constants::$ROUTE_URL_PARAM], '/');

        if (
            isset($this->httpRequestDetails['header'])
            && isset($this->httpRequestDetails['header']['authorization'])
        ) {
            $this->HTTP_AUTHORIZATION = $this->httpRequestDetails['header']['authorization'];
            $this->open = false;
        } elseif ($this->ROUTE === '/login') {
            $this->open = false;
        } else {
            $this->open = true;
        }
        if (!$this->open) {
            $this->auth = new Auth($this);
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        return true;
    }

    /**
     * Load Client Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadClientDetails()
    {
        if (!is_null($this->clientDetails)) return;

        $this->loadCache();

        if ($this->open) {
            $this->clientKey = CacheKey::ClientOpenToWeb($this->HOST);
        } else {
            $this->clientKey = CacheKey::Client($this->HOST);
        }
        if (!$this->cache->cacheExists($this->clientKey)) {
            throw new \Exception("Invalid Host '{$this->HOST}'", HttpStatus::$InternalServerError);
        }

        $this->clientDetails = json_decode($this->cache->getCache($this->clientKey), true);
        $this->clientId = $this->clientDetails['client_id'];

        $this->session['clientDetails'] = &$this->clientDetails;
    }

    /**
     * Loads request payoad
     *
     * @return void
     */
    public function loadPayload()
    {
        if (isset($this->session['payloadType'])) return;

        if ($this->REQUEST_METHOD === Constants::$GET) {
            $this->urlDecode($_GET);
            $this->session['payloadType'] = 'Object';
            $this->session['payload'] = !empty($_GET) ? $_GET : [];
        } else {
            $this->payloadStream = fopen("php://memory", "rw+b");
            if (empty($this->httpRequestDetails['post']['Payload'])) {
                $this->httpRequestDetails['post']['Payload'] = '{}';
            }
            fwrite($this->payloadStream, $this->httpRequestDetails['post']['Payload']);
            
            $this->dataDecode = new DataDecode($this->payloadStream);
            $this->dataDecode->init();

            rewind($this->payloadStream);
            $this->dataDecode->indexData();
            $this->session['payloadType'] = $this->dataDecode->dataType();
        }
    }

    /**
     * Function to find payload is an object/array
     *
     * @param array $arr Array vales to be decoded. Basically $_GET
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

    private function loadCache()
    {
        if (!is_null($this->cache)) {
            return;
        }

        $this->cache = $this->connectCache(
            getenv('cacheType'),
            getenv('cacheHostname'),
            getenv('cachePort'),
            getenv('cacheUsername'),
            getenv('cachePassword'),
            getenv('cacheDatabase')
        );
    }
}
