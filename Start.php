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

namespace Microservices;

use Microservices\Services;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Logs;
use Openswoole\Coroutine;
use Openswoole\Http\Server;
use Openswoole\Http\Request;
use Openswoole\Http\Response;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Autoload.php';

spl_autoload_register(callback: __NAMESPACE__ . '\Autoload::register');

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

        $version = 'v1.0.0';
        if (
            isset($request->get['r'])
            && in_array(
                needle: $request->get['r'],
                haystack: [
                    '/auth-test',
                    '/open-test',
                    '/open-test-xml',
                    '/supp-test'
                ]
            )
        ) {
            $request->header['x-api-version'] = $version;
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
                case '/supp-test':
                    $response->end(processSupplement());
                    break;
            }
            return;
        }

        echo PHP_EOL . $request->server['request_method'] . ':' . $request->get['r'];

        $http = [];
        $http['server']['host'] = 'localhost';
        // $http['server']['host'] = 'public.localhost';
        $http['server']['method'] = $request->server['request_method'];
        $http['server']['ip'] = $request->server['remote_addr'];
        if (isset($request->header['authorization'])) {
            $http['header']['authorization'] = $request->header['authorization'];
        }
        $http['server']['api_version'] = $request->header['x-api-version'];
        $http['get'] = &$request->get;
        $http['post'] = &$request->post;
        $http['files'] = &$request->files;

        // Check version
        if (
            !isset($request->header['x-api-version'])
            || $request->header['x-api-version'] !== $version
        ) {
            // Set response headers
            $response->header('Content-Type', 'application/json; charset=utf-8');
            $cacheControl = 'no-store, no-cache, must-revalidate, max-age=0';
            $response->header('Cache-Control', $cacheControl);
            $response->header('Pragma', 'no-cache');

            $response->end('{"Status": 400, "Message": "Bad Request"}');
            return;
        }

        // Load .env
        $env = parse_ini_file(filename: __DIR__ . DIRECTORY_SEPARATOR . '.env');
        foreach ($env as $key => $value) {
            putenv(assignment: "{$key}={$value}");
        }

        // Code to Initialize / Start the service
        try {
            $services = new Services(http: $http);

            // Setting CORS
            foreach ($services->getHeaders() as $k => $v) {
                $response->header($k, $v);
            }
            if ($http['server']['method'] == 'OPTIONS') {
                $response->end();
                return;
            }

            if ($services->init()) {
                $services->process();
                $response->status($services->c->res->httpStatus);
                $response->end($services->outputResults());
            }
        } catch (\Exception $e) {
            if ($e->getCode() !== 400) {
                // Log request details
                $logDetails = [
                    'LogType' => 'ERROR',
                    'DateTime' => date(format: 'Y-m-d H:i:s'),
                    'HttpDetails' => [
                        'HttpCode' => $e->getCode(),
                        'HttpMessage' => $e->getMessage()
                    ],
                    'Details' => [
                        'http' => $http,
                        'session' => $services->c->req->s
                    ]
                ];
                $logsObj = new Logs();
                $logsObj->log(logDetails: $logDetails);
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

            $dataEncode = new DataEncode(http: $http);
            $dataEncode->init();
            $dataEncode->startObject();
            $dataEncode->addKeyData(key: 'Error', data: $arr);

            $response->end($dataEncode->streamData());
            return;
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
