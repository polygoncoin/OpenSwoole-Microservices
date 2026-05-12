<?php

/**
 * Index
 * php version 8.3
 *
 * @category  Start
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

use Openswoole\Coroutine;
use Openswoole\Http\Server;
use Openswoole\Http\Request;
use Openswoole\Http\Response;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\CommonFunction;
use Microservices\App\Start;
use Microservices\TestCase\Test;

define('ROOT', realpath(path: __DIR__ . DIRECTORY_SEPARATOR . '../'));
define('ROUTE_URL_PARAM', 'route');

require_once ROOT . DIRECTORY_SEPARATOR . 'Autoload.php';
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
	function(Server $server): void {
		echo 'Openswoole http server is started at http://127.0.0.1:9501' . "\n";
	}
);

$server->on(
	'request',
	function(Request $request, Response $response): void {

		// Load .env(s)
		foreach ([
			'.env',
			'.env.cidr',
			'.env.customer.container',
			'.env.enable',
			'.env.global.container',
			'.env.rateLimiting'
		] as $envFilename) {
			$envDataArr = parse_ini_file(filename: ROOT . DIRECTORY_SEPARATOR . $envFilename);
			foreach ($envDataArr as $envVarName => $envVarValue) {
				putenv(assignment: "{$envVarName}={$envVarValue}");
			}
		}

		Constant::init();
		Env::$timestamp = time();
		Env::init();

		$httpReqDetailArr = [];

		$httpReqDetailArr['streamData'] = true;
		// $httpReqDetailArr['server']['domainName'] = 'api.customer001.localhost'; // Auth
		$httpReqDetailArr['server']['domainName'] = 'localhost'; // Open
		$httpReqDetailArr['server']['httpMethod'] = $request->server['request_method'];

		if (
			((int)getenv('DISABLE_REQUESTS_VIA_PROXIES')) === 1
			&& !isset($request->server['remote_addr'])
		) {
			$response->end("Invalid request");
			return;
		}

		if (isset($request->server['remote_addr'])) {
			$httpReqDetailArr['server']['httpRequestIP'] = $request->server['remote_addr'];
		} else {// check proxy headers
			if (isset($request->header['x-forwarded-for'])) {
				$httpReqDetailArr['server']['httpRequestIP'] = $request->header['x-forwarded-for'];
		} elseif (isset($request->header['x-real-ip'])) {
				$httpReqDetailArr['server']['httpRequestIP'] = $request->header['x-real-ip'];
			}
		}

		$httpReqDetailArr['header'] = $request->header;
		if (isset($request->header['authorization'])) {
			$httpReqDetailArr['header']['tokenHeader'] = $request->header['authorization'];
		}
		$httpReqDetailArr['get'] = &$request->get;
		$httpReqDetailArr['post'] = $request->rawContent();
		$httpReqDetailArr['files'] = &$request->files;
		$httpReqDetailArr['httpRequestHash'] = CommonFunction::httpRequestHash(
			hashArray: [
				// $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
				// $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
				// $_SERVER['HTTP_ACCEPT'] ?? '',
				// $_SERVER['HTTP_USER_AGENT'] ?? ''
			]
		);

		if (
			isset($httpReqDetailArr['get'][ROUTE_URL_PARAM])
			&& in_array(
				needle: $httpReqDetailArr['get'][ROUTE_URL_PARAM],
				haystack: [
					'/all-test',
					'/auth-test',
					'/open-test',
					'/open-test-xml',
					'/supp-test'
				]
			)
		) {
			$testObj = new Test();
			switch ($httpReqDetailArr['get'][ROUTE_URL_PARAM]) {
				case '/all-test':
					$response->end('<pre>'.print_r(value: $testObj->processTests(), return: true));
					break;
				case '/auth-test':
					$response->end('<pre>'.print_r(value: $testObj->processAuth(), return: true));
					break;
				case '/open-test':
					$response->end('<pre>'.print_r(value: $testObj->processOpen(), return: true));
					break;
				case '/open-test-xml':
					$response->end('<pre>'.print_r(value: $testObj->processXml(), return: true));
					break;
				case '/supp-test':
					$response->end('<pre>'.print_r(value: $testObj->processSupplement(), return: true));
					break;
			}
		} else {
			ob_start();
			[$responseHeaderArr, $responseContent, $responseCode] = Start::http(httpReqDetailArr: $httpReqDetailArr);
			@ob_clean();

			$responseCode = $responseCode ?? 200;
			$response->status($responseCode);

			foreach ($responseHeaderArr as $headerName => $headerValue) {
				$response->header($headerName, $headerValue);
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
