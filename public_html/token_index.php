<?php

/**
 * Index
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

use Openswoole\Coroutine;
use Openswoole\Http\Server;
use Openswoole\Http\Request;
use Openswoole\Http\Response;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Reload;
use Microservices\App\Start;
use Microservices\TestCase\Test;

define('ROOT', realpath(path: __DIR__ . DIRECTORY_SEPARATOR . '../'));
define('ROUTE_URL_PARAM', 'route');

require_once ROOT . DIRECTORY_SEPARATOR . 'Autoload.php';
spl_autoload_register(
	callback:  'Microservices\Autoload::register'
);

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
		global $DOMAIN_NAME;
		// echo $DOMAIN_NAME . PHP_EOL;

		// Load .env(s)
		foreach ([
			'.env',
			'.env.customer.container',
			'.env.global.container',
			'.env.rateLimiting',
			'.env.route'
		] as $envFilename) {
			$envDataArray = parse_ini_file(
				filename: ROOT . DIRECTORY_SEPARATOR . $envFilename
			);
			foreach ($envDataArray as $envVarName => $envVarValue) {
				putenv(
					assignment: "{$envVarName}={$envVarValue}"
				);
			}
		}

		Constant::init();
		Env::$timestamp = time();
		Env::init();

		$httpReqData = [];

		$httpReqData['streamData'] = Constant::$TRUE;
		$httpReqData['server']['domainName'] = $DOMAIN_NAME;
		$httpReqData['server']['httpRequestMethod'] = $request->server['request_method'];

		if (
			((int)getenv('DISABLE_REQUESTS_VIA_PROXIES')) === 1
			&& !isset($request->server['remote_addr'])
		) {
			$response->end('Invalid request');
			return;
		}

		if (isset($request->server['remote_addr'])) {
			$httpReqData['server']['httpRequestIp'] = $request->server['remote_addr'];
		} else {// check proxy headers
			if (isset($request->header['x-forwarded-for'])) {
				$httpReqData['server']['httpRequestIp'] = $request->header['x-forwarded-for'];
			} elseif (isset($request->header['x-real-ip'])) {
				$httpReqData['server']['httpRequestIp'] = $request->header['x-real-ip'];
			}
		}

		$httpReqData['header'] = $request->header;
		if (isset($httpReqData['header']['content-type'])) {
			$httpReqData['header']['contentType'] = $httpReqData['header']['content-type'];
		} else {
			$httpReqData['header']['contentType'] = '';
		}
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
			$response->end('Missing route');
			return;
		}
		// echo $httpReqData['get'][ROUTE_URL_PARAM] . PHP_EOL;

		$httpReqData['post'] = $request->rawContent();
		$httpReqData['files'] = parseMultipartInput($httpReqData);
		$httpReqData['httpRequestHash'] = httpRequestHash(
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
				],
				strict: Constant::$TRUE
			)
		) {
			$testObject = new Test($httpReqData);
			switch ($httpReqData['get'][ROUTE_URL_PARAM]) {
				case '/all-test':
					$response->end('<pre>'.print_r(value: $testObject->processAllTest(), return: Constant::$TRUE));
					break;
				case '/auth-test':
					$response->end('<pre>'.print_r(value: $testObject->processPrivate(), return: Constant::$TRUE));
					break;
				case '/open-test':
					$response->end('<pre>'.print_r(value: $testObject->processPublic(), return: Constant::$TRUE));
					break;
				case '/open-test-xml':
					$response->end('<pre>'.print_r(value: $testObject->processPublicXml(), return: Constant::$TRUE));
					break;
				case '/supp-test':
					$response->end('<pre>'.print_r(value: $testObject->processPrivateSupplement(), return: Constant::$TRUE));
					break;
			}
		} else {
			if ($httpReqData['get'][ROUTE_URL_PARAM] === '/' . Env::$reloadRequestRoutePrefix) {
				Reload::process(
					httpRequestIp: $httpReqData['server']['httpRequestIp']
				);
				$response->end();
			} else {
				ob_start();
				[
					$responseHeaderArray,
					$responseContent,
					$responseCode
				] = Start::http(
					httpReqData: $httpReqData
				);
				@ob_clean();

				$responseCode = $responseCode ?? HttpStatus::$Ok;
				$response->status($responseCode);

				foreach ($responseHeaderArray as $headerName => $headerValue) {
					$response->header(
						$headerName,
						$headerValue
					);
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
		// HTTP Server max execution time, since v4.8.0
		// 'max_request_execution_time' => 10, // 10s

		// Compression
		'http_compression' => Constant::$TRUE,
		'http_compression_level' => 3, // 1 - 9
		'compression_min_length' => 20,
		'worker_num' =>   2,
		'max_request' =>  1000,
	]
);

$server->start();

/**
 * Unique HTTP request hash
 * 
 * @param array $hashArray Hash array
 * 
 * @return string
 */
function httpRequestHash(
	$hashArray
): string {
	return md5(
		json_encode(
			value: $hashArray
		)
	);
}

/**
 * Parse Multipart Input
 * 
 * @param array $httpReqData HTTP request data
 * 
 * @return array
 */
function parseMultipartInput($httpReqData) {
	$FILES = [];
	// 1. Verify content type and extract boundary
	if (!preg_match('/boundary=(.*)$/', $httpReqData['header']['contentType'], $matches)) {
		return;
	}
	$boundary = $matches[1];

	// 2. Read the raw stream block by block (memory-safe approach)
	$raw_data = $httpReqData['post'];

	if (empty($raw_data)) {
		return;
	}

	// 3. Split the stream using the boundary marker
	$parts = explode("--" . $boundary, $raw_data);

	foreach ($parts as $part) {
		$part = ltrim($part, "\r\n");
		if (empty($part) || $part === "--\r\n" || $part === "--") {
			continue;
		}

		// Separate headers from the binary file payload
		list($headers_block, $body) = explode("\r\n\r\n", $part, 2);
		// Trim trailing carriage return added by boundary layout
		if (substr($body, -2) === "\r\n") {
			$body = substr($body, 0, -2);
		}

		// Parse individual section headers
		$headers = [];
		foreach (explode("\r\n", $headers_block) as $line) {
			list($key, $val) = explode(": ", $line, 2);
			$headers[strtolower($key)] = $val;
		}

		// 4. Look for Content-Disposition to check if it's a file component
		if (isset($headers['content-disposition'])) {
			preg_match('/name="([^"]*)"/', $headers['content-disposition'], $nameMatch);
			$inputName = $nameMatch[1] ?? '';

			if (preg_match('/filename="([^"]*)"/', $headers['content-disposition'], $fileMatch)) {
				// It's a file component!
				$originalName = $fileMatch[1];
				$mimeType = $headers['content-type'] ?? 'application/octet-stream';

				// Write binary body data into a secure system tmp file
				$tmpPath = tempnam(sys_get_temp_dir(), 'php_upload_');
				file_put_contents($tmpPath, $body);

				// 5. Explicitly populate the $_FILES global array
				$FILES[$inputName] = [
					'name' => $originalName,
					'type' => $mimeType,
					'tmp_name' => $tmpPath,
					'error'	=> UPLOAD_ERR_OK,
					'size' => filesize($tmpPath)
				];
			}
		}
		return $FILES;
	}
}
