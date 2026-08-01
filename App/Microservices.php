<?php

/**
 * Service
 * php version 8.3
 * 
 * @category  Microservices
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Dropbox;
use Microservices\App\Env;
use Microservices\App\Gateway;
use Microservices\App\Http;

/**
 * Service
 * php version 8.3
 * 
 * @category  Microservices
 * @package   Openswoole-Microservices
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
	private $startMicroTimestamp = null;

	/**
	 * End micro timestamp;
	 * 
	 * @var null|int
	 */
	private $endMicroTimestamp = null;

	/**
	 * HTTP request data
	 * 
	 * @var null|array
	 */
	public $httpReqData = null;

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	public $httpObject = null;

	/**
	 * Constructor
	 * 
	 * @param array $httpReqData HTTP request data
	 * @throws \Exception
	 */
	public function __construct(
		&$httpReqData
	) {
		$this->httpReqData = &$httpReqData;
		$this->httpObject = new Http(
			$this->httpReqData
		);
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->startMicroTimestamp = microtime(as_float: Constant::$TRUE);
		}

		return $this->httpObject->init();
	}

	/**
	 * Process
	 * 
	 * @return mixed
	 * @throws \Exception
	 */
	public function process(): mixed
	{
		$this->httpObject->initRequest();

		$class = null;

		switch (true) {
			case $this->httpReqData['get'][ROUTE_URL_PARAM] === '/logout':
				$class = __NAMESPACE__ . '\\Logout';
				break;

			// Generates auth token
			case $this->httpReqData['get'][ROUTE_URL_PARAM] === '/login':
				$class = __NAMESPACE__ . '\\Login';
				break;

			// Requires auth token
			default:
				$gateway = new Gateway(
					httpObject: $this->httpObject
				);
				$gateway->init();
				$gateway = null;

				$class = __NAMESPACE__ . '\\Api';
				break;
		}

		// Class found
		try {
			if ($class !== Constant::$NULL) {
				$api = new $class(
					httpObject: $this->httpObject
				);
				if ($api->init()) {
					$this->startData();
					$return = $api->process();
					if (
						is_array(
							value: $return
						)
						&& count(
							value: $return
						) === 3
					) {
						return $return;
					}
					$this->addStatus();
					$this->addPerformance();
					$this->endData();
				}
			}
		} catch (\Exception $e) {
			$this->manageException(
				e: $e
			);
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
		if ($this->httpObject->httpResponseObject === Constant::$NULL) {
			return;
		}
		$this->httpObject->httpResponseObject->dataEncodeObject->startObject();
	}

	/**
	 * Add HTTP status in response
	 * 
	 * @return void
	 */
	public function addStatus(): void
	{
		if ($this->httpObject->httpResponseObject === Constant::$NULL) {
			return;
		}
		$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
			objectKey: 'Status',
			data: $this->httpObject->httpResponseObject->httpStatus
		);
	}

	/**
	 * Add Performance detail in response
	 * 
	 * @return void
	 */
	public function addPerformance(): void
	{
		if ($this->httpObject->httpResponseObject === Constant::$NULL) {
			return;
		}
		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->endMicroTimestamp = microtime(as_float: Constant::$TRUE);
			$time = ceil(
				num: ($this->endMicroTimestamp - $this->startMicroTimestamp) * 1000
			);
			$memory = ceil(
				num: memory_get_peak_usage() / 1000
			);

			$this->httpObject->httpResponseObject->dataEncodeObject->startObject(
				objectKey: 'Stats'
			);
			$this->httpObject->httpResponseObject->dataEncodeObject->startObject(
				objectKey: 'Performance'
			);
			$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
				objectKey: 'total-time-taken',
				data: "{$time} ms"
			);
			$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
				objectKey: 'peak-memory-usage',
				data: "{$memory} KB"
			);
			$this->httpObject->httpResponseObject->dataEncodeObject->endObject();
			$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
				objectKey: 'getrusage',
				data: getrusage()
			);
			$this->httpObject->httpResponseObject->dataEncodeObject->endObject();
		}
	}

	/**
	 * Add Performance detail in response
	 * 
	 * @return array
	 */
	public function returnPerformance(): array
	{
		if ($this->httpObject->httpResponseObject === Constant::$NULL) {
			return [];
		}
		$returnPerformance = [];
		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->endMicroTimestamp = microtime(as_float: Constant::$TRUE);
			$time = ceil(
				num: ($this->endMicroTimestamp - $this->startMicroTimestamp) * 1000
			);
			$memory = ceil(
				num: memory_get_peak_usage() / 1000
			);

			$returnPerformance = [
				'Stats' => [
					'Performance' => [
						'total-time-taken' => "{$time} ms",
						'peak-memory-usage' => "{$memory} KB"
					],
					'getrusage' => getrusage()
				]
			];
		}

		return $returnPerformance;
	}

	/**
	 * End response
	 * 
	 * @return void
	 */
	public function endData(): void
	{
		if ($this->httpObject->httpResponseObject === Constant::$NULL) {
			return;
		}
		$this->httpObject->httpResponseObject->dataEncodeObject->endObject();
		$this->httpObject->httpResponseObject->dataEncodeObject->end();
	}

	/**
	 * Output response
	 * 
	 * @return void
	 */
	public function outputResults(): void
	{
		if ($this->httpObject->httpResponseObject === Constant::$NULL) {
			return;
		}
		http_response_code(response_code: $this->httpObject->httpResponseObject->httpStatus);
		$this->httpObject->httpResponseObject->dataEncodeObject->streamData();
	}

	/**
	 * Return encoded result
	 * 
	 * @return bool|string
	 */
	public function returnResults(): bool|string
	{
		if ($this->httpObject->httpResponseObject === Constant::$NULL) {
			return false;
		}
		return $this->httpObject->httpResponseObject->dataEncodeObject->getData();
	}

	/**
	 * Headers / CORS
	 * 
	 * @return array
	 */
	public function getHeaders(): array
	{
		$headerArray = [];

		// $headerArray['Access-Control-Allow-Origin'] = $this->httpReqData['server']['domainName'];
		$headerArray['Vary'] = 'Origin';
		$headerArray['Access-Control-Allow-Headers'] = '*';

		$headerArray['Referrer-Policy'] = 'origin';
		$headerArray['X-Frame-Options'] = 'SAMEORIGIN';
		$headerArray['X-Content-Type-Options'] = 'nosniff';
		$headerArray['Cross-Origin-Resource-Policy'] = 'same-origin';
		$headerArray['Cross-Origin-Embedder-Policy'] = 'unsafe-none';
		$headerArray['Cross-Origin-Opener-Policy'] = 'unsafe-none';

		// Access-Control header are received during OPTIONS request
		if ($this->httpReqData['server']['httpRequestMethod'] === Constant::$OPTIONS) {
			// may also be using PUT, PATCH, HEAD etc
			$methods = 'GET, QUERY, POST, PUT, PATCH, DELETE, OPTIONS';
			$headerArray['Access-Control-Allow-Methods'] = $methods;
		} else {
			if ($this->httpObject->httpResponseObject === Constant::$NULL) {
				$outputRepresentation = Env::$outputRepresentation;
			} else {
				$outputRepresentation = $this->httpObject->httpResponseObject->outputRepresentation;
			}
			switch ($outputRepresentation) {
				case 'XML':
				case 'XSLT':
					$headerArray['Content-Type'] = 'text/xml; charset=utf-8';
					break;
				case 'JSON':
					$headerArray['Content-Type'] = 'application/json; charset=utf-8';
					break;
				case 'HTML':
				case 'PHP':
					$headerArray['Content-Type'] = 'text/html; charset=utf-8';
					break;
			}
			$cacheControl = 'no-store, no-cache, must-revalidate, max-age=0';
			$headerArray['Cache-Control'] = $cacheControl;
			$headerArray['Pragma'] = 'no-cache';
		}

		return $headerArray;
	}

	/**
	 * Log error
	 * 
	 * @param \Exception $e Exception
	 * 
	 * @return never
	 * @throws \Exception
	 */
	private function manageException(
		$e
	): never {
		throw new \Exception(
			message: $e->getMessage(),
			code: $e->getCode()
		);
	}
}
