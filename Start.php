<?php
namespace Microservices;

use Microservices\Services;

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

$server = new Server("127.0.0.1", 9501);

$server->on("start", function (Server $server) {
    //$server->reload(false);
    echo "OpenSwoole http server is started at http://127.0.0.1:9501\n";
});

$server->on("request", function (Request $request, Response $response) {

    // Load .env
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }

    $httpRequestDetails = [];

    $httpRequestDetails['server']['host'] = 'localhost';
    $httpRequestDetails['server']['request_method'] = $request->server['request_method'];
    $httpRequestDetails['server']['remote_addr'] = $request->server['remote_addr'];
    if (isset($request->header['authorization'])) {
        $httpRequestDetails['header']['authorization'] = $request->header['authorization'];
    }
    $httpRequestDetails['get'] = &$request->get;
    $httpRequestDetails['post'] = &$request->post;
    $httpRequestDetails['files'] = &$request->files;

    // Code to Initialize / Start the service.
    try {
        $services = new Services($httpRequestDetails);

        // Setting CORS
        foreach ($services->getCors() as $k => $v) {
            $response->header($k, $v);
        }
        if ($httpRequestDetails['server']['request_method'] == 'OPTIONS') {
            $response->end();
            return;
        }

        if ($services->init()) {
            $services->process();
            $response->end($services->outputResults());
        }
    } catch (\Exception $e) {
        $arr = [
            'Status' => $e->getCode(),
            'Message' => $e->getMessage()
        ];
        $response->end(json_encode($arr));
    }
});

/**
 *  https://openswoole.com/docs/modules/swoole-server/configuration
 */
$server->set([
    // HTTP Server max execution time, since v4.8.0
    'max_request_execution_time' => 10, // 10s

    // Compression
    'http_compression' => true,
    'http_compression_level' => 3, // 1 - 9
    'compression_min_length' => 20,
]);

$server->start();
