<?php

/**
 * HTTP response
 * php version 8.3
 * 
 * @category  HTTP response
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * HTTP response
 * php version 8.3
 * 
 * @category  HTTP response
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpResponse
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
	 * HTTP Status
	 * 
	 * @var int
	 */
	public $httpStatus;

	/**
	 * Data Encode object
	 * 
	 * @var null|DataEncode
	 */
	public $dataEncodeObject = null;

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
		$this->httpStatus = HttpStatus::$Ok;

		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->startMicroTimestamp = microtime(as_float: Constant::$TRUE);
		}
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		$outputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: [],
			httpReqData: $this->httpObject->httpReqData
		);
		$this->dataEncodeObject = new DataEncode(
			httpObject: $this->httpObject,
			outputRepresentation: $outputRepresentation
		);

		$this->dataEncodeObject->init();

		return Constant::$TRUE;
	}


	/**
	 * Start Data Output
	 * 
	 * @return void
	 */
	public function startData(): void
	{
		$this->dataEncodeObject->startObject();
	}

	/**
	 * End response
	 * 
	 * @return void
	 */
	public function endData(): void
	{
		$this->dataEncodeObject->endObject();
		$this->dataEncodeObject->end();
	}

	/**
	 * Add HTTP status in response
	 * 
	 * @return void
	 */
	public function addStatus(): void
	{
		$this->dataEncodeObject->addKeyData(
			objectKey: 'Status',
			data: $this->httpStatus
		);
	}

	/**
	 * Add Performance detail in response
	 * 
	 * @return void
	 */
	public function addPerformance(): void
	{
		if (Env::$OUTPUT_PERFORMANCE_STATS) {
			$this->endMicroTimestamp = microtime(as_float: Constant::$TRUE);
			$time = ceil(
				num: ($this->endMicroTimestamp - $this->startMicroTimestamp) * 1000
			);
			$memory = ceil(
				num: memory_get_peak_usage() / 1000
			);

			$this->dataEncodeObject->startObject(
				objectKey: 'Stats'
			);
			$this->dataEncodeObject->startObject(
				objectKey: 'Performance'
			);
			$this->dataEncodeObject->addKeyData(
				objectKey: 'total-time-taken',
				data: "{$time} ms"
			);
			$this->dataEncodeObject->addKeyData(
				objectKey: 'peak-memory-usage',
				data: "{$memory} KB"
			);
			$this->dataEncodeObject->endObject();
			$this->dataEncodeObject->addKeyData(
				objectKey: 'getrusage',
				data: getrusage()
			);
			$this->dataEncodeObject->endObject();
		}
	}

	/**
	 * Add Performance detail in response
	 * 
	 * @return array
	 */
	public function returnPerformance(): array
	{
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
}
