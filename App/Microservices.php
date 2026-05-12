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

use Microservices\App\CommonFunction;
use Microservices\App\Dropbox;
use Microservices\App\Env;
use Microservices\App\Gateway;
use Microservices\App\Http;
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
	 * HTTP request detail
	 *
	 * @var null|array
	 */
	public $httpReqDetailArr = null;

	/**
	 * HTTP object
	 *
	 * @var null|Http
	 */
	public $http = null;

	/**
	 * Constructor
	 *
	 * @param array $httpReqDetailArr HTTP request detail
	 */
	public function __construct(&$httpReqDetailArr)
	{
		$this->httpReqDetailArr = &$httpReqDetailArr;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function init(): bool
	{
		if (!isset($this->httpReqDetailArr['get'][ROUTE_URL_PARAM])) {
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
						haystack: $this->httpReqDetailArr['get'][ROUTE_URL_PARAM],
						needle: '/' . Env::$cronRequestRoutePrefix
					) === 0
				):
				if ($this->httpReqDetailArr['server']['httpRequestIP'] !== Env::$cronRestrictedCidr) {
					throw new \Exception(
						message: 'Source IP is not supported',
						code: HttpStatus::$NotFound
					);
				}
				$class = __NAMESPACE__ . '\\Cron';
				break;

			case $this->httpReqDetailArr['get'][ROUTE_URL_PARAM] === '/logout':
				$class = __NAMESPACE__ . '\\Logout';
				break;

			// Requires HTTP auth username and password
			case (
					Env::$enableReloadRequest
					&& $this->httpReqDetailArr['get'][ROUTE_URL_PARAM] === '/' . Env::$reloadRequestRoutePrefix
				):
				$isValidIp = CommonFunction::checkCidr(
					IP: $this->httpReqDetailArr['server']['httpRequestIP'],
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
			case $this->httpReqDetailArr['get'][ROUTE_URL_PARAM] === '/login':
				$this->http = new Http($this->httpReqDetailArr);
				$this->http->init();
				$class = __NAMESPACE__ . '\\Login';
				break;

			// Requires auth token
			default:
				if ($this->httpReqDetailArr['server']['httpMethod'] === Constant::$GET) {
					$dropboxCache = new Dropbox(httpReqDetailArr: $this->httpReqDetailArr);
					if ($dropboxCache->init(mode: 'Open')) {
						// File exists - Serve from Dropbox
						return $dropboxCache->process();
					}
					$dropboxCache = null;
				}

				$this->http = new Http($this->httpReqDetailArr);
				$this->http->init();

				$gateway = new Gateway($this->http);
				$gateway->init();
				$gateway = null;

				$class = __NAMESPACE__ . '\\Api';
				break;
		}

		// Class found
		try {
			if ($class !== null) {
				$api = new $class($this->http);
				if ($api->init()) {
					if ($this->http !== null) {
						$this->http->initResponse();
					}
					$this->startData();
					$return = $api->process();
					if (
						is_array($return)
						&& count($return) === 3
					) {
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
	 * Start Data Output
	 *
	 * @return void
	 */
	public function startData(): void
	{
		if ($this->http === null) {
			return;
		}
		$this->http->res->dataEncode->startObject();
	}

	/**
	 * Add HTTP status in response
	 *
	 * @return void
	 */
	public function addStatus(): void
	{
		if ($this->http === null) {
			return;
		}
		$this->http->res->dataEncode->addKeyData(
			objectKey: 'Status',
			data: $this->http->res->httpStatus
		);
	}

	/**
	 * Add Performance detail in response
	 *
	 * @return void
	 */
	public function addPerformance(): void
	{
		if ($this->http === null) {
			return;
		}
		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->tsEnd = microtime(as_float: true);
			$time = ceil(num: ($this->tsEnd - $this->tsStart) * 1000);
			$memory = ceil(num: memory_get_peak_usage() / 1000);

			$this->http->res->dataEncode->startObject(objectKey: 'Stats');
			$this->http->res->dataEncode->startObject(objectKey: 'Performance');
			$this->http->res->dataEncode->addKeyData(
				objectKey: 'total-time-taken',
				data: "{$time} ms"
			);
			$this->http->res->dataEncode->addKeyData(
				objectKey: 'peak-memory-usage',
				data: "{$memory} KB"
			);
			$this->http->res->dataEncode->endObject();
			$this->http->res->dataEncode->addKeyData(
				objectKey: 'getrusage',
				data: getrusage()
			);
			$this->http->res->dataEncode->endObject();
		}
	}

	/**
	 * End response
	 *
	 * @return void
	 */
	public function endData(): void
	{
		if ($this->http === null) {
			return;
		}
		$this->http->res->dataEncode->endObject();
		$this->http->res->dataEncode->end();
	}

	/**
	 * Output response
	 *
	 * @return void
	 */
	public function outputResults(): void
	{
		if ($this->http === null) {
			return;
		}
		http_response_code(response_code: $this->http->res->httpStatus);
		$this->http->res->dataEncode->streamData();
	}

	/**
	 * Return encoded result
	 *
	 * @return bool|string
	 */
	public function returnResults(): bool|string
	{
		if ($this->http === null) {
			return false;
		}
		return $this->http->res->dataEncode->getData();
	}

	/**
	 * Headers / CORS
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		$headerArr = [];
		$headerArr['Access-Control-Allow-Origin'] = $this->httpReqDetailArr['server']['domainName'];
		$headerArr['Vary'] = 'Origin';
		$headerArr['Access-Control-Allow-Headers'] = '*';

		$headerArr['Referrer-Policy'] = 'origin';
		$headerArr['X-Frame-Options'] = 'SAMEORIGIN';
		$headerArr['X-Content-Type-Options'] = 'nosniff';
		$headerArr['Cross-Origin-Resource-Policy'] = 'same-origin';
		$headerArr['Cross-Origin-Embedder-Policy'] = 'unsafe-none';
		$headerArr['Cross-Origin-Opener-Policy'] = 'unsafe-none';

		// Access-Control header are received during OPTIONS request
		if ($this->httpReqDetailArr['server']['httpMethod'] == 'OPTIONS') {
			// may also be using PUT, PATCH, HEAD etc
			$methods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
			$headerArr['Access-Control-Allow-Methods'] = $methods;
		} else {
			if ($this->http === null) {
				$oRepresentation = Env::$oRepresentation;
			} else {
				$oRepresentation = $this->http->res->oRepresentation;
			}
			switch ($oRepresentation) {
				case 'XML':
				case 'XSLT':
					$headerArr['Content-Type'] = 'text/xml; charset=utf-8';
					break;
				case 'JSON':
					$headerArr['Content-Type'] = 'application/json; charset=utf-8';
					break;
				case 'HTML':
				case 'PHP':
					$headerArr['Content-Type'] = 'text/html; charset=utf-8';
					break;
			}
			$cacheControl = 'no-store, no-cache, must-revalidate, max-age=0';
			$headerArr['Cache-Control'] = $cacheControl;
			$headerArr['Pragma'] = 'no-cache';
		}

		return $headerArr;
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
