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
        Constants::init();
        Env::init();

        $this->c = new Common();
        $this->c->init($request, $response);

        $this->setCors();

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
        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400'); // cache for 1 day
        }
        
        // Access-Control headers are received during OPTIONS requests
        if ($this->c->request->server['request_method'] == 'OPTIONS') {
            
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                // may also be using PUT, PATCH, HEAD etc
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        
            exit(0);
        }
    }

    /**
     * HTTP Authentication
     *
     * @param string $envUsername env variable to match username
     * @param string $envPassword env variable to match password
     * @return boolean
     */
    private function httpAuthentication($envUsername, $envPassword)
    {
        // Check request not from proxy.
        if (
            !isset($this->c->request->server['remote_addr']) ||
            $this->c->request->server['remote_addr'] !== getenv('HttpAuthenticationRestrictedIp')
        ) {
            http_response_code(404);
        }
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            header('WWW-Authenticate: Basic realm="Test Authentication System"');
            header('HTTP/1.0 401 Unauthorized');
            echo "You must enter a valid login ID and password to access this resource\n";
            return false;
        } else {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
    
            $validated = ($username === getenv($envUsername)) && ($password === getenv($envPassword));
    
            if (!$validated) {
                header('WWW-Authenticate: Basic realm="My Realm"');
                header('HTTP/1.0 401 Unauthorized');
                die ("Not authorized");
            } else {
                return true;
            }
        }
    }
}
