<?php

/**
 * Session Index
 * php version 8.3
 *
 * @category  Start
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

use Openswoole\Http\Server;
use Openswoole\Http\Request;
use Openswoole\Http\Response;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\CommonFunction;
use Microservices\App\HttpStatus;
use Microservices\App\Reload;
use Microservices\App\SessionHandler\Session;
use Microservices\App\Start;
use Microservices\TestCase\Test;

define('ROOT', realpath(path: __DIR__ . DIRECTORY_SEPARATOR . '../'));
define('ROUTE_URL_PARAM', 'route');

require_once ROOT . DIRECTORY_SEPARATOR . 'Autoload.php';
spl_autoload_register(callback:  'Microservices\Autoload::register');

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
			'.env.customer.container',
			'.env.global.container',
			'.env.rateLimiting',
			'.env.route'
		] as $envFilename) {
			$envDataArr = parse_ini_file(filename: ROOT . DIRECTORY_SEPARATOR . $envFilename);
			foreach ($envDataArr as $envVarName => $envVarValue) {
				putenv(assignment: "{$envVarName}={$envVarValue}");
			}
		}

		Constant::init();
		Env::$timestamp = time();
		Env::init();

		if (Env::$authMode === 'Session') {
			// Initialize Session Handler
			Session::initSessionHandler(sessionMode: Env::$sessionMode, options: []);

			// Start session in readonly mode
			Session::sessionStartReadonly();
		}

		$httpReqData = [];

		$httpReqData['streamData'] = true;
		$httpReqData['server']['domainName'] = 'api.customer001.localhost'; // Private
		// $httpReqData['server']['domainName'] = 'localhost'; // Public
		$httpReqData['server']['httpMethod'] = $request->server['request_method'];

		if (
			((int)getenv('DISABLE_REQUESTS_VIA_PROXIES')) === 1
			&& !isset($request->server['remote_addr'])
		) {
			$response->end("Invalid request");
			return;
		}

		if (isset($request->server['remote_addr'])) {
			$httpReqData['server']['httpRequestIP'] = $request->server['remote_addr'];
		} else {// check proxy headers
			if (isset($request->header['x-forwarded-for'])) {
				$httpReqData['server']['httpRequestIP'] = $request->header['x-forwarded-for'];
			} elseif (isset($request->header['x-real-ip'])) {
				$httpReqData['server']['httpRequestIP'] = $request->header['x-real-ip'];
			}
		}

		$httpReqData['header'] = $request->header;
		if (isset($request->header['authorization'])) {
			$httpReqData['header']['tokenHeader'] = $request->header['authorization'];
		}
		$httpReqData['get'] = &$request->get;
		if (isset($httpReqData['get'][ROUTE_URL_PARAM])) {
			$httpReqData['get'][ROUTE_URL_PARAM] = '/' . trim(
				string: $httpReqData['get'][ROUTE_URL_PARAM],
				characters: '/'
			);
		} else {
			throw new \Exception(
				message: 'Missing route',
				code: HttpStatus::$NotFound
			);
		}

		$httpReqData['post'] = $request->rawContent();
		$httpReqData['files'] = &$request->files;
		$httpReqData['httpRequestHash'] = CommonFunction::httpRequestHash(
			hashArray: [
				// $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
				// $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
				// $_SERVER['HTTP_ACCEPT'] ?? '',
				// $_SERVER['HTTP_USER_AGENT'] ?? ''
			]
		);

		if (
			isset($httpReqData['get'][ROUTE_URL_PARAM])
			&& in_array(
				needle: $httpReqData['get'][ROUTE_URL_PARAM],
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
			switch ($httpReqData['get'][ROUTE_URL_PARAM]) {
				case '/all-test':
					$response->end('<pre>'.print_r(value: $testObj->processAllTest(), return: true));
					break;
				case '/auth-test':
					$response->end('<pre>'.print_r(value: $testObj->processPrivate(), return: true));
					break;
				case '/open-test':
					$response->end('<pre>'.print_r(value: $testObj->processPublic(), return: true));
					break;
				case '/open-test-xml':
					$response->end('<pre>'.print_r(value: $testObj->processPublicXml(), return: true));
					break;
				case '/supp-test':
					$response->end('<pre>'.print_r(value: $testObj->processPrivateSupplement(), return: true));
					break;
			}
		} else {
			if ($httpReqData['get'][ROUTE_URL_PARAM] === '/' . Env::$reloadRequestRoutePrefix) {
				Reload::process();
				$response->end();
			} else {
				ob_start();
				[$responseHeaderArr, $responseContent, $responseCode] = Start::http(httpReqData: $httpReqData);
				@ob_clean();

				$responseCode = $responseCode ?? 200;
				$response->status($responseCode);

				foreach ($responseHeaderArr as $headerName => $headerValue) {
					$response->header($headerName, $headerValue);
				}
				$response->end($responseContent);
			}
		}
	}
);

/*
 * https://openswoole.com/docs/modules/swoole-server/configuration
 */
$server->set(
	[
		// Disable Coroutines for Traditional PHP Sessions
		'enable_coroutine' => false,
	]
);

$server->start();
