<?php

/**
 * Start
 * php version 8.3
 *
 * @category  Start
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

use Openswoole\Coroutine;
use Openswoole\Http\Server;
use Openswoole\Http\Request;
use Openswoole\Http\Response;

use Microservices\App\Constants;
use Microservices\App\Env;
use Microservices\App\Start;
use Microservices\TestCases\Tests;

define('PUBLIC_HTML', __DIR__);

require_once PUBLIC_HTML . DIRECTORY_SEPARATOR . 'Autoload.php';
spl_autoload_register(callback:  'Microservices\Autoload::register');

// Set coroutine options before you start a server...
Coroutine::set(
    [
        'max_coroutine' => 100,
        'max_concurrency' => 100,
    ]
);

$server = new Server('127.0.0.1', 9501);

$server->on(
    'start',
    function (Server $server): void {
        echo 'Openswoole http server is started at http://127.0.0.1:9501' . "\n";
    }
);

$server->on(
    'request',
    function (Request $request, Response $response): void {

        $http = [];
        $http['server']['host'] = 'localhost';
        // $http['server']['host'] = 'public.localhost';
        $http['server']['method'] = $request->server['request_method'];
        $http['server']['ip'] = $request->server['remote_addr'];
        if (isset($request->header['authorization'])) {
            $http['header']['authorization'] = $request->header['authorization'];
        }
        $http['get'] = &$request->get;
        $http['post'] = &$request->post;
        $http['files'] = &$request->files;

        if (
            isset($http['get']['r'])
            && in_array(
                needle: $http['get']['r'],
                haystack: [
                    '/auth-test',
                    '/open-test',
                    '/open-test-xml',
                    '/supp-test'
                ]
            )
        ) {
            $tests = new Tests();
            switch ($http['get']['r']) {
                case '/auth-test':
                    $response->end($tests->processAuth());
                    break;
                case '/open-test':
                    $response->end($tests->processOpen());
                    break;
                case '/open-test-xml':
                    $response->end($tests->processXml());
                    break;
                case '/supp-test':
                    $response->end($tests->processSupplement());
                    break;
            }
        } else {
            // Load .env
            $env = parse_ini_file(filename: __DIR__ . DIRECTORY_SEPARATOR . '.env');
            foreach ($env as $key => $value) {
                putenv(assignment: "{$key}={$value}");
            }

            Constants::init();
            Env::init(http: $http);

            [$responseheaders, $responseContent, $responseCode] = Start::http(http: $http, streamData: true);

            $response->status($responseCode);
            foreach ($responseheaders as $k => $v) {
                $response->header($k, $v);
            }
            $response->end($responseContent);
        }
    }
);

/*
 * https://openswoole.com/docs/modules/swoole-server/configuration
 */
$server->set(
    [
        // HTTP Server max execution time, since v4.8.0
        // 'max_request_execution_time' => 10, // 10s

        // Compression
        'http_compression' => true,
        'http_compression_level' => 3, // 1 - 9
        'compression_min_length' => 20,
        'worker_num' =>   2,
        'max_request' =>  1000,
    ]
);

$server->start();
