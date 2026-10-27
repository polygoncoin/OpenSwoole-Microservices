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
	public $encodeObject = null;

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

		$this->startMicroTimestamp = microtime(as_float: Constant::$TRUE);
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		$sqlConfig = $this->httpObject->httpReqData['active']['sqlData']['Config'] ?? [];

		$this->encodeObject = new DataEncode(
			httpObject: $this->httpObject
		);

		$this->encodeObject->init();

		return Constant::$TRUE;
	}


	/**
	 * Start Data Output
	 * 
	 * @return void
	 */
	public function startData(): void
	{
		$this->encodeObject->startObject();
	}

	/**
	 * End response
	 * 
	 * @return void
	 */
	public function endData(): void
	{
		$this->encodeObject->endObject();
		$this->encodeObject->end();
	}

	/**
	 * Add HTTP status in response
	 * 
	 * @return void
	 */
	public function addStatus(): void
	{
		$this->encodeObject->addKeyData(
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
		if ($this->httpObject->Env->OUTPUT_PERFORMANCE_STATS) {
			$this->endMicroTimestamp = microtime(as_float: Constant::$TRUE);
			$time = ceil(
				num: ($this->endMicroTimestamp - $this->startMicroTimestamp) * 1000
			);
			$memory = ceil(
				num: memory_get_peak_usage() / 1000
			);

			$this->encodeObject->startObject(
				objectKey: 'Stats'
			);
			$this->encodeObject->startObject(
				objectKey: 'Performance'
			);
			$this->encodeObject->addKeyData(
				objectKey: 'total-time-taken',
				data: "{$time} ms"
			);
			$this->encodeObject->addKeyData(
				objectKey: 'peak-memory-usage',
				data: "{$memory} KB"
			);
			$this->encodeObject->endObject();
			$this->encodeObject->addKeyData(
				objectKey: 'getrusage',
				data: getrusage()
			);
			$this->encodeObject->endObject();
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
		if ($this->httpObject->Env->OUTPUT_PERFORMANCE_STATS) {
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
