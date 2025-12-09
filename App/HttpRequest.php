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
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\DbFunctions;
use Microservices\App\HttpStatus;
use Microservices\App\Middleware\Auth;
use Microservices\App\RouteParser;

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
        $this->loadClientDetails();

        if (!$this->open) {
            $this->auth->loadUserDetails();
            $this->auth->loadGroupDetails();
        }

        $this->rParser->parseRoute();
        DbFunctions::setDatabaseCacheKey();

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
        switch (true) {
            case (
                Common::$req->rParser->isImportRequest
                && isset($this->http['files']['file']['tmp_name'])
            ):
                $content = $this->formatCsvPayload(
                    csvFile: $this->http['files']['file']['tmp_name']
                );
                break;
            case Env::$iRepresentation === 'XML':
                $content = $this->convertXmlToJson(xmlString: $this->http['post']);
                break;
            default:
                $content = $this->http['post'];
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

    /**
     * Format Csv Payload
     *
     * @param string $csvFile
     *
     * @return string
     */
    public function formatCsvPayload($csvFile): string
    {
        $dataEncode = new DataEncode(http: $this->http);
        $dataEncode->init(header: false);
        $dataEncode->startObject();

        $header = false;
        $counter = null;
        $modeArr = [];

        $fp = fopen($csvFile, "r");
        while (($line = fgets($fp)) !== false) {
            if (empty($line)) {
                continue;
            }
            $data = str_getcsv($line, ",", "\"", "\\");
            if (empty($data)) {
                continue;
            }
            if ($header === false) {
                $headerData = [];
                foreach ($data as $key => $value) {
                    $v = explode(':', $value);
                    $_headerData = &$headerData;
                    for (
                        $i = 0, $iCount = count($v);
                        $i < $iCount;
                        $i++
                    ) {
                        if (($i+1) === $iCount) {
                            $_headerData['__column__'][$v[$i]] = $key;
                        } else {
                            if (!isset($_headerData[$v[$i]])) {
                                $_headerData[$v[$i]] = [];
                            }
                            $_headerData = &$_headerData[$v[$i]];
                        }
                    }
                }
                $header = $headerData;
                $counter = 0;
                continue;
            }

            [$_mode, $_data] = $this->formatCsvArray($header, $data);

            if ($counter === 0) {
                $modeArr = $_mode;
                $dataEncode->startArray(key: $_mode[0]);
                $dataEncode->startObject();
                foreach ($_data as $k => $v) {
                    $dataEncode->addKeyData(key: $k, data: $v);
                }
                $counter = 1;
                continue;
            }

            if ($modeArr === $_mode) {
                $dataEncode->endObject();
                $dataEncode->startObject();
            } else {
                $_modeArr = [];
                $modeCount = count($modeArr);
                $_modeCount = count($_mode);

                for (
                    $i = 0;
                    $i < $_modeCount;
                    $i++
                ) {
                    if (
                        !isset($modeArr[$i])
                        || ($modeArr[$i] !== $_mode[$i])
                    ) {
                        break;
                    }
                    $_modeArr[$i] = $_mode[$i];
                }
                if ($_modeCount < $modeCount) {
                    for ($_i = $_modeCount; $_i < $modeCount; $_i++) {
                        $dataEncode->endObject();
                        $dataEncode->endArray();
                    }
                    $dataEncode->endObject();
                    $dataEncode->startObject();
                }
                if ($i < $_modeCount) {
                    for ($_i = $i; $_i < $modeCount; $_i++) {
                        $dataEncode->endObject();
                        $dataEncode->endArray();
                    }
                    for ($_i = $i; $_i < $_modeCount; $_i++) {
                        $_modeArr[$_i] = $_mode[$_i];
                        $dataEncode->startArray(key: $_mode[$_i]);
                        $dataEncode->startObject();
                    }
                }
                $modeArr = $_modeArr;
            }
            foreach ($_data as $k => $v) {
                $dataEncode->addKeyData(key: $k, data: $v);
            }
        }
        $dataEncode->endObject();
        $json = $dataEncode->getData();
        $dataEncode = null;
        $json = substr($json, 7, (strlen($json)-8));

        return $json;
    }

    /**
     * Format Csv Payload
     *
     * @param string $csvContent CSV string
     *
     * @return array
     */
    public function formatCsvArray($header, $data): array
    {
        $_data = [];
        $_mode = explode(':', $data[0]);
        $_header = &$header;

        foreach ($_mode as $v) {
            if (!isset($_header[$v])) {
                return [];
            }
            $_header = &$_header[$v];
        }

        foreach ($_header['__column__'] as $field => $col) {
            if (!isset($data[$col])) {
                return [];
            }
            $_data[$field] = $data[$col];
        }
        return [$_mode, $_data];
    }
}
