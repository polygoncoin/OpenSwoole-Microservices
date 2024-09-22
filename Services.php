<?php
namespace Microservices;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
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
     */
    private $tsStart = null;

    /**
     * End micro timestamp;
     */
    private $tsEnd = null;

    /**
     * Microservices Request Details
     * 
     * @var array
     */
    public $inputs = null;

    /**
     * Microservices Collection of Common Objects
     * 
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param array $inputs
     * @return void
     */
    public function __construct(&$inputs)
    {
        $this->inputs = &$inputs;

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
        $this->c = new Common($this->inputs);
        $this->c->init();

        if (!isset($this->inputs['get'][Constants::$ROUTE_URL_PARAM])) {
            throw new \Exception('Missing route', 404);
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
     * Output
     *
     * @return void
     */
    public function outputResults()
    {
        if (!is_null($this->c->httpResponse->output)) {
            return $this->c->httpResponse->output;
        } else {
            return $this->c->httpResponse->jsonEncode->streamJson();
        }
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
        if ($this->inputs['server']['request_method'] == 'OPTIONS') {
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
     * @param object $e Exception
     * @return void
     */
    private function log($e)
    {
        $log = [
            'datetime' => date('Y-m-d H:i:s'),
            'input' => $this->c->httpRequest->input,
            "code" => $e->getCode(),
            "msg" => $e->getMessage(),
            "e" => json_encode($e)
        ];
        (new Logs)->log('error', json_encode($log));

        throw new \Exception($e->getMessage(), $e->getCode());
    }
}
