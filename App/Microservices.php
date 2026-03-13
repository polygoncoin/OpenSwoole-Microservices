<?php

/**
 * Service
 * php version 8.3
 *
 * @category  Microservices
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Http;
use Microservices\App\Env;
use Microservices\App\CommonFunction;
use Microservices\App\Gateway;
use Microservices\App\HttpStatus;

/**
 * Service
 * php version 8.3
 *
 * @category  Microservices
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Microservices
{
	/**
	 * Start micro timestamp;
	 *
	 * @var null|int
	 */
	private $tsStart = null;

	/**
	 * End micro timestamp;
	 *
	 * @var null|int
	 */
	private $tsEnd = null;

	/**
	 * Http Request Details
	 *
	 * @var null|array
	 */
	public $iConfig = null;

	/**
	 * Http Object
	 *
	 * @var null|Http
	 */
	public $http = null;

	/**
	 * Constructor
	 *
	 * @param array $iConfig Http Request Details
	 */
	public function __construct(&$iConfig)
	{
		$this->iConfig = &$iConfig;
		$this->http = new Http($this->iConfig);
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function init(): bool
	{
		$this->http->init(iConfig: $this->iConfig);

		if (!isset($this->iConfig['get'][ROUTE_URL_PARAM])) {
			throw new \Exception(
				message: 'Missing route',
				code: HttpStatus::$NotFound
			);
		}

		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->tsStart = microtime(as_float: true);
		}

		return true;
	}

	/**
	 * Process
	 *
	 * @return mixed
	 */
	public function process(): mixed
	{
		return $this->processApi();
	}

	/**
	 * Start Data Output
	 *
	 * @return void
	 */
	public function startData(): void
	{
		$this->http->res->dataEncode->startObject();
	}

	/**
	 * Process API request
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function processApi(): mixed
	{
		$class = null;

		switch (true) {
			case (
					Env::$enableCronRequest
					&& strpos(
						haystack: $this->http->iConfig['get'][ROUTE_URL_PARAM],
						needle: '/' . Env::$cronRequestRoutePrefix
					) === 0
				):
				if ($this->http->iConfig['server']['httpRequestIP'] !== Env::$cronRestrictedCidr) {
					throw new \Exception(
						message: 'Source IP is not supported',
						code: HttpStatus::$NotFound
					);
				}
				$class = __NAMESPACE__ . '\\Cron';
				break;

			case $this->http->iConfig['get'][ROUTE_URL_PARAM] === '/logout':
				$class = __NAMESPACE__ . '\\Logout';
				break;

			// Requires HTTP auth username and password
			case (
					Env::$enableReloadRequest
					&& $this->http->iConfig['get'][ROUTE_URL_PARAM] === '/' . Env::$reloadRequestRoutePrefix
				):
				$isValidIp = CommonFunction::checkCidr(
					IP: $this->http->iConfig['server']['httpRequestIP'],
					cidrString: Env::$reloadRestrictedCidr
				);
				if (!$isValidIp) {
					throw new \Exception(
						message: 'Source IP is not supported',
						code: HttpStatus::$NotFound
					);
				}
				$class = __NAMESPACE__ . '\\Reload';
				break;

			// Generates auth token
			case $this->http->iConfig['get'][ROUTE_URL_PARAM] === '/login':
				$class = __NAMESPACE__ . '\\Login';
				break;

			// Requires auth token
			default:
				$gateway = new Gateway($this->http);
				$gateway->initGateway();
				$gateway = null;

				$class = __NAMESPACE__ . '\\Api';
				break;
		}

		// Class found
		try {
			if ($class !== null) {
				$api = new $class($this->http);
				if ($api->init()) {
					$this->http->initResponse();
					$this->startData();
					$return = $api->process();
					if (is_array($return) && count($return) === 3) {
						return $return;
					}
					$this->addStatus();
					$this->addPerformance();
					$this->endData();
				}
			}
		} catch (\Exception $e) {
			$this->log(e: $e);
		}

		return true;
	}

	/**
	 * Add Status
	 *
	 * @return void
	 */
	public function addStatus(): void
	{
		$this->http->res->dataEncode->addKeyData(
			key: 'Status',
			data: $this->http->res->httpStatus
		);
	}

	/**
	 * Add Performance details
	 *
	 * @return void
	 */
	public function addPerformance(): void
	{
		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->tsEnd = microtime(as_float: true);
			$time = ceil(num: ($this->tsEnd - $this->tsStart) * 1000);
			$memory = ceil(num: memory_get_peak_usage() / 1000);

			$this->http->res->dataEncode->startObject(key: 'Stats');
			$this->http->res->dataEncode->startObject(key: 'Performance');
			$this->http->res->dataEncode->addKeyData(
				key: 'total-time-taken',
				data: "{$time} ms"
			);
			$this->http->res->dataEncode->addKeyData(
				key: 'peak-memory-usage',
				data: "{$memory} KB"
			);
			$this->http->res->dataEncode->endObject();
			$this->http->res->dataEncode->addKeyData(
				key: 'getrusage',
				data: getrusage()
			);
			$this->http->res->dataEncode->endObject();
		}
	}

	/**
	 * End Data Output
	 *
	 * @return void
	 */
	public function endData(): void
	{
		$this->http->res->dataEncode->endObject();
		$this->http->res->dataEncode->end();
	}

	/**
	 * Output
	 *
	 * @return void
	 */
	public function outputResults(): void
	{
		http_response_code(response_code: $this->http->res->httpStatus);
		$this->http->res->dataEncode->streamData();
	}

	/**
	 * Output
	 *
	 * @return bool|string
	 */
	public function returnResults(): bool|string
	{
		return $this->http->res->dataEncode->getData();
	}

	/**
	 * Headers / CORS
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		$headers = [];
		$headers['Access-Control-Allow-Origin'] = $this->iConfig['server']['domainName'];
		$headers['Vary'] = 'Origin';
		$headers['Access-Control-Allow-Headers'] = '*';

		$headers['Referrer-Policy'] = 'origin';
		$headers['X-Frame-Options'] = 'SAMEORIGIN';
		$headers['X-Content-Type-Options'] = 'nosniff';
		$headers['Cross-Origin-Resource-Policy'] = 'same-origin';
		$headers['Cross-Origin-Embedder-Policy'] = 'unsafe-none';
		$headers['Cross-Origin-Opener-Policy'] = 'unsafe-none';

		// Access-Control headers are received during OPTIONS request
		if ($this->iConfig['server']['httpMethod'] == 'OPTIONS') {
			// may also be using PUT, PATCH, HEAD etc
			$methods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
			$headers['Access-Control-Allow-Methods'] = $methods;
		} else {
			switch ($this->http->res->oRepresentation) {
				case 'XML':
				case 'XSLT':
					$headers['Content-Type'] = 'text/xml; charset=utf-8';
					break;
				case 'JSON':
					$headers['Content-Type'] = 'application/json; charset=utf-8';
					break;
				case 'HTML':
				case 'PHP':
					$headers['Content-Type'] = 'text/html; charset=utf-8';
					break;
			}
			$cacheControl = 'no-store, no-cache, must-revalidate, max-age=0';
			$headers['Cache-Control'] = $cacheControl;
			$headers['Pragma'] = 'no-cache';
		}

		return $headers;
	}

	/**
	 * Log error
	 *
	 * @param \Exception $e Exception
	 *
	 * @return never
	 * @throws \Exception
	 */
	private function log($e): never
	{
		throw new \Exception(
			message: $e->getMessage(),
			code: $e->getCode()
		);
	}
}
