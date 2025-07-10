<?php
/**
 * Service
 * php version 8.3
 *
 * @category  Service
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * Service
 * php version 8.3
 *
 * @category  Service
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Services
{
    /**
     * Start micro timestamp;
     *
     * @var null|int
     */
    private $_tsStart = null;

    /**
     * End micro timestamp;
     *
     * @var null|int
     */
    private $_tsEnd = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $http = null;

    /**
     * Common Object
     *
     * @var null|Common
     */
    public $c = null;

    /**
     * Constructor
     *
     * @param array $http HTTP request details
     *
     * @return void
     */
    public function __construct(&$http)
    {
        $this->http = &$http;

        Constants::init();
        Env::init(http: $http);
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->c = new Common(http: $this->http);
        $this->c->init();

        if (!isset($this->http['get'][Constants::$ROUTE_URL_PARAM])) {
            throw new \Exception(
                message: 'Missing route',
                code: HttpStatus::$NotFound
            );
        }

        if (Env::$OUTPUT_PERFORMANCE_STATS) {
            $this->_tsStart = microtime(as_float: true);
        }

        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        $this->startJson();
        $this->processApi();
        $this->endOutputJson();
        $this->addPerformance();
        $this->endJson();

        return true;
    }

    /**
     * Start Json
     *
     * @return void
     */
    public function startJson(): void
    {
        $this->c->res->dataEncode->startObject();
    }

    /**
     * Process API request
     *
     * @return bool
     */
    public function processApi(): bool
    {
        $class = null;

        switch (true) {

        case Env::$allowCronRequest && strpos(
            haystack: $this->c->req->ROUTE,
            needle: '/' . Env::$cronRequestUriPrefix
        ) === 0:
            if ($this->c->req->REMOTE_ADDR !== Env::$cronRestrictedIp) {
                throw new \Exception(
                    message: 'Source IP is not supported',
                    code: HttpStatus::$NotFound
                );
            }
            $class = __NAMESPACE__ . '\\App\\Cron';
            break;

        // Requires HTTP auth username and password
        case $this->c->req->ROUTE === '/reload':
            if ($this->c->req->REMOTE_ADDR !== Env::$cronRestrictedIp) {
                throw new \Exception(
                    message: 'Source IP is not supported',
                    code: HttpStatus::$NotFound
                );
            }
            $class = __NAMESPACE__ . '\\App\\Reload';
            break;

        // Generates auth token
        case $this->c->req->ROUTE === '/login':
            $class = __NAMESPACE__ . '\\App\\Login';
            break;

        // Requires auth token
        default:
            $this->c->req->initGateway();
            $class = __NAMESPACE__ . '\\App\\Api';
            break;
        }

        // Class found
        try {
            if (!is_null(value: $class)) {
                $api = new $class($this->c);
                if ($api->init()) {
                    $api->process();
                }
            }
        } catch (\Exception $e) {
            $this->_log($e);
        }

        return true;
    }

    /**
     * End Json Output Key
     *
     * @return void
     */
    public function endOutputJson(): void
    {
        $this->c->res->dataEncode->addKeyData(
            key: 'Status',
            data: $this->c->res->httpStatus
        );
    }

    /**
     * Add Performance details
     *
     * @return void
     */
    public function addPerformance(): void
    {
        if (Env::$OUTPUT_PERFORMANCE_STATS) {
            $this->_tsEnd = microtime(as_float: true);
            $time = ceil(num: ($this->_tsEnd - $this->_tsStart) * 1000);
            $memory = ceil(num: memory_get_peak_usage() / 1000);

            $this->c->res->dataEncode->startObject(key: 'Stats');
            $this->c->res->dataEncode->startObject(key: 'Performance');
            $this->c->res->dataEncode->addKeyData(
                key: 'total-time-taken',
                data: "{$time} ms"
            );
            $this->c->res->dataEncode->addKeyData(
                key: 'peak-memory-usage',
                data: "{$memory} KB"
            );
            $this->c->res->dataEncode->endObject();
            $this->c->res->dataEncode->addKeyData(
                key: 'getrusage',
                data: getrusage()
            );
            $this->c->res->dataEncode->endObject();
        }
    }

    /**
     * End Json
     *
     * @return void
     */
    public function endJson(): void
    {
        $this->c->res->dataEncode->endObject();
        $this->c->res->dataEncode->end();
    }

    /**
     * Output
     *
     * @return bool|string
     */
    public function outputResults(): bool|string
    {
        return $this->c->res->dataEncode->streamData();
    }

    /**
     * Headers / CORS
     *
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [];
        $headers['Access-Control-Allow-Origin'] = $this->http['server']['host'];
        $headers['Vary'] = 'Origin';
        $headers['Access-Control-Allow-Headers'] = '*';

        $headers['Referrer-Policy'] = 'origin';
        $headers['X-Frame-Options'] = 'SAMEORIGIN';
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['Cross-Origin-Resource-Policy'] = 'same-origin';
        $headers['Cross-Origin-Embedder-Policy'] = 'unsafe-none';
        $headers['Cross-Origin-Opener-Policy'] = 'unsafe-none';

        // Access-Control headers are received during OPTIONS requests
        if ($this->http['server']['request_method'] == 'OPTIONS') {
            // may also be using PUT, PATCH, HEAD etc
            $methods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
            $headers['Access-Control-Allow-Methods'] = $methods;
        } else {
            if (Env::$outputRepresentation === 'Xml') { // XML headers
                $headers['Content-Type'] = 'text/xml; charset=utf-8';
            } else { // JSON headers
                $headers['Content-Type'] = 'application/json; charset=utf-8';
            }
            $cacheControl = 'no-store, no-cache, must-revalidate, max-age=0';
            $headers['Cache-Control'] = $cacheControl;
            $headers['Pragma'] = 'no-cache';
        }

        return $headers;
    }

    /**
     * Log error
     *
     * @param \Exception $e Exception
     *
     * @return void
     */
    private function _log($e): never
    {
        throw new \Exception(
            message: $e->getMessage(),
            code: $e->getCode()
        );
    }
}
