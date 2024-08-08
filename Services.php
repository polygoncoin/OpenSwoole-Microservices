<?php
namespace Microservices;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

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
     */
    private $tsStart = null;

    /**
     * End micro timestamp;
     */
    private $tsEnd = null;

    /**
     * OpenSwoole Http Request
     * 
     * @var OpenSwoole\Http\Request
     */
    private $request = null;

    /**
     * OpenSwoole Http Response
     * 
     * @var OpenSwoole\Http\Response
     */
    private $response = null;

    /**
     * Microservices Collection of Common Objects
     * 
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Constructor
     */
    public function __construct()
    {
    }
    
    /**
     * Initialize
     *
     * @param OpenSwoole\Http\Request  $request
     * @param OpenSwoole\Http\Response $response
     * @return boolean
     */
    public function init(Request &$request, Response &$response)
    {
        $this->request = $request;
        $this->response = $response;

        if (!$this->setCors()) {
            return false;
        }

        Constants::init();
        Env::init();

        $this->c = new Common();
        $this->c->init($request, $response);

        if (!isset($this->c->request->get[Constants::$ROUTE_URL_PARAM])) {
            $this->c->response->end('Missing route');
        }

        if (Env::$OUTPUT_PERFORMANCE_STATS) {
            $this->tsStart = microtime(true);
        }

        return $this->c->httpResponse->isSuccess();
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        if ($this->c->httpResponse->isSuccess()) $this->startJson();
        if ($this->c->httpResponse->isSuccess()) $this->startOutputJson();
        if ($this->c->httpResponse->isSuccess()) $this->processApi();
        if ($this->c->httpResponse->isSuccess()) $this->endOutputJson();
        if ($this->c->httpResponse->isSuccess()) $this->addPerformance();
        if ($this->c->httpResponse->isSuccess()) $this->endJson();

        return $this->c->httpResponse->isSuccess();
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
        $this->c->httpResponse->jsonEncode->startObject('Output');      
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

            case strpos($this->c->httpRequest->ROUTE, '/cron') === 0:
                if ($this->c->httpRequest->REMOTE_ADDR !== Env::$cronRestrictedIp) {
                    $this->c->httpResponse->return4xx(404, 'Source IP is not supported');
                    return;
                }
                $class = __NAMESPACE__ . '\\App\\Cron';
                break;
            
            // Requires HTTP auth username and password
            case $this->c->httpRequest->ROUTE === '/reload':
                if ($this->c->httpRequest->REMOTE_ADDR !== Env::$cronRestrictedIp) {
                    $this->c->httpResponse->return4xx(404, 'Source IP is not supported');
                    return;
                }
                $class = __NAMESPACE__ . '\\App\\Reload';
                break;
            
            // Generates auth token
            case $this->c->httpRequest->ROUTE === '/login':
                $class = __NAMESPACE__ . '\\App\\Login';
                break;

            // Requires auth token
            default:
                $class = __NAMESPACE__ . '\\App\\Api';
                break;
        }

        // Class found
        if (!is_null($class)) {
            $api = new $class($this->c);
            if ($api->init()) {
                $api->process();
            }
        }

        return $this->c->httpResponse->isSuccess();
    }

    /**
     * End Json Output Key
     *
     * @return void
     */
    public function endOutputJson()
    {
        $this->c->httpResponse->jsonEncode->endObject();
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
            $memory = ceil(memory_get_peak_usage()/1000);
        
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
     * End Json
     *
     * @return void
     */
    public function streamJson()
    {
        $this->c->httpResponse->setHeaders();
        if (!is_null($this->c->httpResponse->output)) {
            $this->c->response->end($this->c->httpResponse->output);
        } else {
            $this->c->httpResponse->jsonEncode->streamJson();
        }
    }

    /**
     * Output
     *
     * @return void
     */
    public function outputResults()
    {
        $this->streamJson();
    }

    /**
     * CORS-compliant method
     * 
     * @return void
     */
    private function setCors()
    {
        $this->response->header('Access-Control-Allow-Origin', '*');
        $this->response->header('Access-Control-Allow-Headers', '*');

        // Access-Control headers are received during OPTIONS requests
        if ($this->request->server['request_method'] == 'OPTIONS') {
            
            // may also be using PUT, PATCH, HEAD etc
            $this->response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $this->response->end();

            return false;
        }
        return true;
    }
}
