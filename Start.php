<?php
namespace Microservices;

use Microservices\Services;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Logs;

use OpenSwoole\Coroutine;
use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

/**
 * Class to autoload class files
 *
 * @category   Autoload
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Autoload
{
    /**
     * Autoload Register function
     *
     * @param string $className
     * @return void
     */
    static public function register($className)
    {
        $className = substr($className, strlen(__NAMESPACE__));
        $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
        $file = __DIR__ . $className . '.php';
        if (!file_exists($file)) {
            echo PHP_EOL . "File '{$file}' missing" . PHP_EOL;
        }
        require $file;
    }
}

spl_autoload_register(__NAMESPACE__ . '\Autoload::register');

// Set coroutine options before you start a server...
Coroutine::set([
    'max_coroutine' => 100,
    'max_concurrency' => 100,
]);

$server = new Server("127.0.0.1", 9501);

$server->on("start", function (Server $server) {
    //$server->reload(false);
    echo "OpenSwoole http server is started at http://127.0.0.1:9501\n";
});

$server->on("request", function (Request $request, Response $response) use ($server) {

    if (isset($request->get['r']) && in_array($request->get['r'], ['/auth-test', '/open-test', '/open-test-xml'])) {
        include __DIR__ . '/Tests.php';
        switch ($request->get['r']) {
            case '/auth-test':
                $response->end(processAuth());
                break;        
            case '/open-test':
                $response->end(processOpen());
                break;        
            case '/open-test-xml':
                $response->end(processXml());
                break;        
        }
        return;
    }

    $httpRequestDetails = [];

    $httpRequestDetails['server']['host'] = 'localhost';
    // $httpRequestDetails['server']['host'] = 'public.localhost';
    $httpRequestDetails['server']['request_method'] = $request->server['request_method'];
    $httpRequestDetails['server']['remote_addr'] = $request->server['remote_addr'];
    if (isset($request->header['authorization'])) {
        $httpRequestDetails['header']['authorization'] = $request->header['authorization'];
    }
    $httpRequestDetails['server']['api_version'] = $request->header['x-api-version'];
    $httpRequestDetails['get'] = &$request->get;
    $httpRequestDetails['post'] = &$request->post;
    $httpRequestDetails['files'] = &$request->files;

    // Check version
    if (!isset($request->header['x-api-version']) || $request->header['x-api-version'] !== 'v1.0.0') {
        // Set response headers
        $response->header('Content-Type', 'application/json; charset=utf-8');
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');

        $response->end('{"Status": 400, "Message": "Bad Request"}');
        return;
    }

    // Load .env
    $env = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . '.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }

    // Code to Initialize / Start the service
    try {
        $services = new Services($httpRequestDetails);

        // Setting CORS
        foreach ($services->getHeaders() as $k => $v) {
            $response->header($k, $v);
        }
        if ($httpRequestDetails['server']['request_method'] == 'OPTIONS') {
            $response->end();
            return;
        }

        if ($services->init()) {
            $services->process();
            $response->status($services->c->httpResponse->httpStatus);
            $response->end($services->outputResults());
        }
    } catch (\Exception $e) {
        if ($e->getCode() !== 400) {
            // Log request details
            $logDetails = [
                'LogType' => 'ERROR',
                'DateTime' => date('Y-m-d H:i:s'),
                'HttpDetails' => [
                    "HttpCode" => $e->getCode(),
                    "HttpMessage" => $e->getMessage()
                ],
                'Details' => [
                    'httpRequestDetails' => $httpRequestDetails,
                    'session' => $services->c->httpRequest->session
                ]
            ];
            (new Logs)->log($logDetails);
        }

        $response->status($e->getCode());

        if ($e->getCode() == 429) {
            $response->header('Retry-After:', $e->getMessage());
            $arr = [
                'Status' => $e->getCode(),
                'Message' => 'Too Many Requests',
                'RetryAfter' => $e->getMessage()
            ];
        } else {
            $arr = [
                'Status' => $e->getCode(),
                'Message' => $e->getMessage()
            ];
        }

        $dataEncode = new DataEncode($httpRequestDetails);
        $dataEncode->init();
        $dataEncode->startObject();
        $dataEncode->addKeyData('Error', $arr);

        $response->end($dataEncode->streamData());
        return;
    }
});

/**
 *  https://openswoole.com/docs/modules/swoole-server/configuration
 */
$server->set([
    // HTTP Server max execution time, since v4.8.0
    // 'max_request_execution_time' => 10, // 10s

    // Compression
    'http_compression' => true,
    'http_compression_level' => 3, // 1 - 9
    'compression_min_length' => 20,
    'worker_num' =>   2,
    'max_request' =>  1000,
]);

$server->start();
