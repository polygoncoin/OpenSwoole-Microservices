<?php

/**
 * HTTP Request
 * php version 8.3
 *
 * @category  HTTP_Request
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\DataRepresentation\DataDecode;
use Microservices\App\DbFunctions;
use Microservices\App\HttpStatus;
use Microservices\App\Middleware\Auth;
use Microservices\App\RouteParser;
use Microservices\App\SessionHandlers\Session;

/**
 * HTTP Request
 * php version 8.3
 *
 * @category  HTTP_Request
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpRequest
{
    /**
     * SQL Cache object
     *
     * @var null|Object
     */
    public $sqlCache = null;

    /**
     * Auth middleware object
     *
     * @var null|Auth
     */
    public $auth = null;

    /**
     * JSON Decode object
     *
     * @var null|DataDecode
     */
    public $dataDecode = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $http = null;

    /**
     * Session details of a request
     *
     * @var null|array
     */
    public $s = null;

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
    public $METHOD = null;
    public $HTTP_AUTHORIZATION = null;
    public $IP = null;
    public $ROUTE = null;

    /**
     * Payload stream
     */
    public $payloadStream = null;

    /**
     * Route Parser object
     *
     * @var null|RouteParser
     */
    public $rParser = null;

    /**
     * Constructor
     *
     * @param array $http HTTP request details
     */
    public function __construct(&$http)
    {
        $this->http = &$http;

        $this->HOST = $this->http['server']['host'];
        $this->METHOD = $this->http['server']['method'];
        $this->IP = $this->http['server']['ip'];
        if (isset($this->http['get'][ROUTE_URL_PARAM])) {
            $this->ROUTE = '/' . trim(
                string: $this->http['get'][ROUTE_URL_PARAM],
                characters: '/'
            );
        } else {
            $this->ROUTE = '';
        }

        switch (Env::$authMode) {
            case 'Token':
                if (
                    isset($this->http['header'])
                    && isset($this->http['header']['authorization'])
                ) {
                    $this->HTTP_AUTHORIZATION = $this->http['header']['authorization'];
                    $this->open = false;
                } elseif ($this->ROUTE === '/login') {
                    $this->open = false;
                } else {
                    $this->open = true;
                }
                break;
            case 'Session':
                if (
                    isset($_SESSION)
                    && isset($_SESSION['id'])
                ) {
                    $this->open = false;
                } elseif ($this->ROUTE === '/login') {
                    $this->open = false;
                } else {
                    $this->open = true;
                }
                break;
        }

        if (!$this->open) {
            $this->auth = new Auth();
        }

        $this->rParser = new RouteParser();
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
        if (isset($this->s['cDetails'])) {
            return;
        }

        $this->loadCache();

        if ($this->open) {
            $cKey = CacheKey::clientOpenToWeb(hostname: $this->HOST);
        } else {
            $cKey = CacheKey::client(hostname: $this->HOST);
        }
        if (!DbFunctions::$gCacheServer->cacheExists(key: $cKey)) {
            throw new \Exception(
                message: "Invalid Host '{$this->HOST}'",
                code: HttpStatus::$InternalServerError
            );
        }

        $this->s['cDetails'] = json_decode(
            json: DbFunctions::$gCacheServer->getCache(
                key: $cKey
            ),
            associative: true
        );
    }

    /**
     * Loads request payload
     *
     * @return void
     */
    public function loadPayload(): void
    {
        if (isset($this->s['payloadType'])) {
            return;
        }

        $this->s['queryParams'] = &$this->http['get'];
        if ($this->METHOD === Constants::$GET) {
            $this->urlDecode(value: $this->http['get']);
            $this->s['payloadType'] = 'Object';
        } else {
            $this->setPayloadStream();
            rewind(stream: $this->payloadStream);

            $this->dataDecode = new DataDecode(
                dataFileHandle: $this->payloadStream
            );
            $this->dataDecode->init();

            $this->dataDecode->indexData();
            $this->s['payloadType'] = $this->dataDecode->dataType();
        }
    }

    /**
     * Set payload stream
     *
     * @return void
     */
    private function setPayloadStream(): void
    {
        $content = $this->http['post'];
        if (Env::$iRepresentation === 'XML') {
            $content = $this->convertXmlToJson(xmlString: $content);
        }
        $this->payloadStream = fopen(
            filename: "php://memory",
            mode: "rw+b"
        );
        fwrite(
            stream: $this->payloadStream,
            data: $content
        );
    }

    /**
     * Convert XML to JSON
     *
     * @param string $xmlString
     *
     * @return string
     */
    private function convertXmlToJson($xmlString): string
    {
        $xml = simplexml_load_string(
            data: $xmlString
        );
        $array = json_decode(
            json: json_encode(value: $xml),
            associative: true
        );
        unset($xml);

        $result = [];
        $this->formatXmlArray(array: $array, result: $result);

        return json_encode($result);
    }

    /**
     * Format Array generated by XML
     *
     * @param array $array  Array generated by XML
     * @param array $result Formatted array
     *
     * @return void
     */
    private function formatXmlArray(&$array, &$result): void
    {
        if (isset($array['Rows']) && is_array(value: $array['Rows'])) {
            $array = &$array['Rows'];
        }

        if (isset($array['Row']) && is_array(value: $array['Row'])) {
            $array = &$array['Row'];
        }

        if (
            isset($array[0])
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
                $this->formatXmlArray(array: $value, result: $result[$key]);
                continue;
            }
            $result[$key] = $value;
        }
    }

    /**
     * Function to find payload is an object/array
     *
     * @param array|string $value Array vales to be decoded. Basically $http['get']
     *
     * @return void
     */
    public function urlDecode(&$value): void
    {
        if (is_array(value: $value)) {
            foreach ($value as &$v) {
                if (is_array(value: $v)) {
                    $this->urlDecode(value: $v);
                } else {
                    $v = urldecode(string: $v);
                }
            }
        } else {
            $value = urldecode(string: $value);
        }
    }

    /**
     * Load cache server
     *
     * @return void
     */
    private function loadCache(): void
    {
        DbFunctions::connectGlobalCache();
    }
}
