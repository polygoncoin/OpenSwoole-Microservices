<?php
namespace Microservices;

use Microservices\App\ApiGateway;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Logs;

/**
 * Microservices Class
 *
 * Class to start Services.
 *
 * @category   Services
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Services
{
    /**
     * Start micro timestamp;
     *
     * @var null|integer
     */
    private $tsStart = null;

    /**
     * End micro timestamp;
     *
     * @var null|integer
     */
    private $tsEnd = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $httpRequestDetails = null;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * @var null|ApiGateway
     */
    private $apiGateway = null;

    /**
     * Constructor
     *
     * @param array $httpRequestDetails
     * @return void
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->apiGateway = new ApiGateway($httpRequestDetails);
        $this->httpRequestDetails = &$httpRequestDetails;

        Constants::init();
        Env::init();
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->c = new Common($this->httpRequestDetails);
        $this->c->init();

        if (!isset($this->httpRequestDetails['get'][Constants::$ROUTE_URL_PARAM])) {
            throw new \Exception('Missing route', 404);
        }

        if (Env::$OUTPUT_PERFORMANCE_STATS) {
            $this->tsStart = microtime(true);
        }

        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $this->startJson();
        $this->startOutputJson();
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
    public function startJson()
    {
        $this->c->httpResponse->jsonEncode->startObject();
    }

    /**
     * Start Json Output Key
     *
     * @return void
     */
    public function startOutputJson()
    {
        // $this->c->httpResponse->jsonEncode->startObject('Output');
    }

    /**
     * Process API request
     *
     * @return boolean
     */
    public function processApi()
    {
        $class = null;

        switch (true) {

            case Env::$allowCronRequest && strpos($this->c->httpRequest->ROUTE, '/' . Env::$cronRequestUriPrefix) === 0:
                if ($this->c->httpRequest->REMOTE_ADDR !== Env::$cronRestrictedIp) {
                    throw new \Exception('Source IP is not supported', 404);
                }
                $class = __NAMESPACE__ . '\\App\\Cron';
                break;

            // Requires HTTP auth username and password
            case $this->c->httpRequest->ROUTE === '/reload':
                if ($this->c->httpRequest->REMOTE_ADDR !== Env::$cronRestrictedIp) {
                    throw new \Exception('Source IP is not supported', 404);
                }
                $class = __NAMESPACE__ . '\\App\\Reload';
                break;

            // Generates auth token
            case $this->c->httpRequest->ROUTE === '/login':
                $class = __NAMESPACE__ . '\\App\\Login';
                break;

            // Requires auth token
            default:
                $this->apiGateway->init();
                $class = __NAMESPACE__ . '\\App\\Api';
                break;
        }
        $this->apiGateway = null;

        // Class found
        try {
            if (!is_null($class)) {
                $api = new $class($this->c);
                if ($api->init()) {
                    $api->process();
                }
            }
        } catch (\Exception $e) {
            $this->log($e);
        }

        return true;
    }

    /**
     * End Json Output Key
     *
     * @return void
     */
    public function endOutputJson()
    {
        // $this->c->httpResponse->jsonEncode->endObject();
        $this->c->httpResponse->jsonEncode->addKeyValue('Status', $this->c->httpResponse->httpStatus);
    }

    /**
     * Add Performance details
     *
     * @return void
     */
    public function addPerformance()
    {
        if (Env::$OUTPUT_PERFORMANCE_STATS) {
            $this->tsEnd = microtime(true);
            $time = ceil(($this->tsEnd - $this->tsStart) * 1000);
            $memory = ceil(memory_get_peak_usage() / 1000);

            $this->c->httpResponse->jsonEncode->startObject('Stats');
            $this->c->httpResponse->jsonEncode->startObject('Performance');
            $this->c->httpResponse->jsonEncode->addKeyValue('total-time-taken', "{$time} ms");
            $this->c->httpResponse->jsonEncode->addKeyValue('peak-memory-usage', "{$memory} KB");
            $this->c->httpResponse->jsonEncode->endObject();
            $this->c->httpResponse->jsonEncode->addKeyValue('getrusage', getrusage());
            $this->c->httpResponse->jsonEncode->endObject();
        }
    }

    /**
     * End Json
     *
     * @return void
     */
    public function endJson()
    {
        $this->c->httpResponse->jsonEncode->endObject();
        $this->c->httpResponse->jsonEncode->end();
    }

    /**
     * Output
     *
     * @return void
     */
    public function outputResults()
    {
        return $this->c->httpResponse->jsonEncode->streamJson();
    }

    /**
     * CORS-compliant method
     *
     * @return void
     */
    public function getCors()
    {
        $headers = [];
        $headers['Access-Control-Allow-Origin'] = '*';
        $headers['Access-Control-Allow-Headers'] = '*';

        // Access-Control headers are received during OPTIONS requests
        if ($this->httpRequestDetails['server']['request_method'] == 'OPTIONS') {
            // may also be using PUT, PATCH, HEAD etc
            $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
        } else {
            // JSON headers
            $headers['Content-Type'] = 'application/json;charset=utf-8';
            $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
            $headers['Pragma'] = 'no-cache';
        }

        return $headers;
    }

    /**
     * Log error
     *
     * @param \Exception $e
     * @return void
     */
    private function log($e)
    {
        $log = [
            'datetime' => date('Y-m-d H:i:s'),
            'conditions' => $this->c->httpRequest->conditions,
            "code" => $e->getCode(),
            "msg" => $e->getMessage(),
            "e" => json_encode($e)
        ];
        (new Logs)->log('error', json_encode($log));

        throw new \Exception($e->getMessage(), $e->getCode());
    }
}
