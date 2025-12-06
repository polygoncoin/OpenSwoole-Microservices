<?php

/**
 * Microservices
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

namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Gateway;
use Microservices\App\HttpStatus;

/**
 * Microservices
 * php version 8.3
 *
 * @category  Microservices
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Microservices
{
    /**
     * Start micro timestamp;
     *
     * @var null|int
     */
    private $tsStart = null;

    /**
     * End micro timestamp;
     *
     * @var null|int
     */
    private $tsEnd = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $http = null;

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
    }

    /**
     * Initialize
     *
     * @return bool
     * @throws \Exception
     */
    public function init(): bool
    {
        Common::init(http: $this->http);

        if (!isset($this->http['get'][ROUTE_URL_PARAM])) {
            throw new \Exception(
                message: 'Missing route',
                code: HttpStatus::$NotFound
            );
        }

        if (Env::$OUTPUT_PERFORMANCE_STATS) {
            $this->tsStart = microtime(as_float: true);
        }

        return true;
    }

    /**
     * Process
     *
     * @return mixed
     */
    public function process(): mixed
    {
        return $this->processApi();
    }

    /**
     * Start Data Output
     *
     * @return void
     */
    public function startData(): void
    {
        Common::$res->dataEncode->startObject();
    }

    /**
     * Process API request
     *
     * @return mixed
     * @throws \Exception
     */
    public function processApi(): mixed
    {
        $class = null;

        switch (true) {
            case Env::$allowCronRequest && strpos(
                haystack: Common::$req->ROUTE,
                needle: '/' . Env::$cronRequestRoutePrefix
            ) === 0:
                if (Common::$req->IP !== Env::$cronRestrictedCidr) {
                    throw new \Exception(
                        message: 'Source IP is not supported',
                        code: HttpStatus::$NotFound
                    );
                }
                $class = __NAMESPACE__ . '\\Cron';
                break;

            case Common::$req->ROUTE === '/logout':
                $class = __NAMESPACE__ . '\\Logout';
                break;

            // Requires HTTP auth username and password
            case Common::$req->ROUTE === '/reload':
                if (Common::$req->IP !== Env::$cronRestrictedCidr) {
                    throw new \Exception(
                        message: 'Source IP is not supported',
                        code: HttpStatus::$NotFound
                    );
                }
                $class = __NAMESPACE__ . '\\Reload';
                break;

            // Generates auth token
            case Common::$req->ROUTE === '/login':
                $class = __NAMESPACE__ . '\\Login';
                break;

            // Requires auth token
            default:
                $gateway = new Gateway();
                $gateway->initGateway();
                $gateway = null;

                $class = __NAMESPACE__ . '\\Api';
                break;
        }

        // Class found
        try {
            if ($class !== null) {
                $api = new $class();
                if ($api->init()) {
                    Common::initResponse();
                    $this->startData();
                    $return = $api->process();
                    if (is_array($return) && count($return) === 3) {
                        return $return;
                    }
                    $this->addStatus();
                    $this->addPerformance();
                    $this->endData();
                }
            }
        } catch (\Exception $e) {
            $this->log(e: $e);
        }

        return true;
    }

    /**
     * Add Status
     *
     * @return void
     */
    public function addStatus(): void
    {
        Common::$res->dataEncode->addKeyData(
            key: 'Status',
            data: Common::$res->httpStatus
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
            $this->tsEnd = microtime(as_float: true);
            $time = ceil(num: ($this->tsEnd - $this->tsStart) * 1000);
            $memory = ceil(num: memory_get_peak_usage() / 1000);

            Common::$res->dataEncode->startObject(key: 'Stats');
            Common::$res->dataEncode->startObject(key: 'Performance');
            Common::$res->dataEncode->addKeyData(
                key: 'total-time-taken',
                data: "{$time} ms"
            );
            Common::$res->dataEncode->addKeyData(
                key: 'peak-memory-usage',
                data: "{$memory} KB"
            );
            Common::$res->dataEncode->endObject();
            Common::$res->dataEncode->addKeyData(
                key: 'getrusage',
                data: getrusage()
            );
            Common::$res->dataEncode->endObject();
        }
    }

    /**
     * End Data Output
     *
     * @return void
     */
    public function endData(): void
    {
        Common::$res->dataEncode->endObject();
        Common::$res->dataEncode->end();
    }

    /**
     * Output
     *
     * @return void
     */
    public function outputResults(): void
    {
        http_response_code(response_code: Common::$res->httpStatus);
        Common::$res->dataEncode->streamData();
    }

    /**
     * Output
     *
     * @return bool|string
     */
    public function returnResults(): bool|string
    {
        return Common::$res->dataEncode->getData();
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
        if ($this->http['server']['method'] == 'OPTIONS') {
            // may also be using PUT, PATCH, HEAD etc
            $methods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
            $headers['Access-Control-Allow-Methods'] = $methods;
        } else {
            switch (Env::$oRepresentation) {
                case 'XML':
                    $headers['Content-Type'] = 'text/xml; charset=utf-8';
                    break;
                case 'JSON':
                    $headers['Content-Type'] = 'application/json; charset=utf-8';
                    break;
                case 'HTML':
                    $headers['Content-Type'] = 'text/html; charset=utf-8';
                    break;
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
     * @return never
     * @throws \Exception
     */
    private function log($e): never
    {
        throw new \Exception(
            message: $e->getMessage(),
            code: $e->getCode()
        );
    }
}
