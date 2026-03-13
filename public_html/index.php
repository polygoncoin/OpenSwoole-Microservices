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
			$env = parse_ini_file(filename: ROOT . DIRECTORY_SEPARATOR . $envFilename);
			foreach ($env as $key => $value) {
				putenv(assignment: "{$key}={$value}");
			}
		}

		Constant::init();
		Env::$timestamp = time();
		Env::init();

		$iConfig = [];
		$iConfig['server']['domainName'] = 'api.customer001.localhost'; // Auth
		// $iConfig['server']['domainName'] = 'localhost'; // Open
		$iConfig['server']['httpMethod'] = $request->server['request_method'];

		if (
			((int)getenv('DISABLE_REQUESTS_VIA_PROXIES')) === 1
			&& !isset($request->server['remote_addr'])
		) {
			$response->end("Invalid request");
			return;
		}

		if (isset($request->server['remote_addr'])) {
			$iConfig['server']['httpRequestIP'] = $request->server['remote_addr'];
		} else {// check proxy headers
			if (isset($request->header['x-forwarded-for'])) {
				$iConfig['server']['httpRequestIP'] = $request->header['x-forwarded-for'];
		} elseif (isset($request->header['x-real-ip'])) {
				$iConfig['server']['httpRequestIP'] = $request->header['x-real-ip'];
			}
		}

		$iConfig['header'] = $request->header;
		$iConfig['get'] = &$request->get;
		$iConfig['post'] = $request->rawContent();
		$iConfig['files'] = &$request->files;
		$iConfig['httpRequestHash'] = CommonFunction::httpRequestHash(
			hashArray: [
				// $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
				// $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
				// $_SERVER['HTTP_ACCEPT'] ?? '',
				// $_SERVER['HTTP_USER_AGENT'] ?? ''
			]
		);

		if (
			isset($iConfig['get'][ROUTE_URL_PARAM])
			&& in_array(
				needle: $iConfig['get'][ROUTE_URL_PARAM],
				haystack: [
					'/tests',
					'/auth-test',
					'/open-test',
					'/open-test-xml',
					'/supp-test'
				]
			)
		) {
			$tests = new Test();
			switch ($iConfig['get'][ROUTE_URL_PARAM]) {
				case '/tests':
					$response->end('<pre>'.print_r(value: $tests->processTests(), return: true));
					break;
				case '/auth-test':
					$response->end('<pre>'.print_r(value: $tests->processAuth(), return: true));
					break;
				case '/open-test':
					$response->end('<pre>'.print_r(value: $tests->processOpen(), return: true));
					break;
				case '/open-test-xml':
					$response->end('<pre>'.print_r(value: $tests->processXml(), return: true));
					break;
				case '/supp-test':
					$response->end('<pre>'.print_r(value: $tests->processSupplement(), return: true));
					break;
			}
		} else {
			ob_start();
			[$responseheaders, $responseContent, $responseCode] = Start::http(iConfig: $iConfig, streamData: true);
			@ob_clean();

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
