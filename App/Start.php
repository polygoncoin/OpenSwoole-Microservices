<?php

/**
 * Start
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

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Microservices;
use Microservices\App\Log;

/**
 * Start
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
class Start
{
	/**
	 * Process HTTP request data
	 *
	 * @param array $httpReqData HTTP request data
	 *
	 * @return array
	 */
	public static function http(&$httpReqData)
	{
		$headerArr = [];

		if ($httpReqData['server']['httpMethod'] == Constant::$POST) {
			$startArrayPos = strpos($httpReqData['post'], '[');
			$startObjectPos = strpos($httpReqData['post'], '{');
			$startXmlPos = strpos($httpReqData['post'], '<');
			if (
				$startArrayPos !== 0
				&& $startObjectPos !== 0
				&& $startXmlPos !== 0
			) {
				parse_str($httpReqData['post'], $httpReqData['post']);
				$httpReqData['post'] = json_encode($httpReqData['post']);
			}
		}

		try {
			$Microservices = new Microservices(httpReqData: $httpReqData);

			if (
				$httpReqData['streamData']
				&& $httpReqData['server']['httpMethod'] == Constant::$OPTIONS
			) {
				// Setting CORS
				$headerArr = $Microservices->getHeaders();
				$data = '{}';
				$status = HttpStatus::$Ok;

				return [$headerArr, $data, $status];
			}

			if ($Microservices->init()) {
				// Setting CORS
				if ($httpReqData['streamData']) {
					$headerArr = $Microservices->getHeaders();
				}

				$return = $Microservices->process();
				if (
					is_array($return)
					&& count($return) === 3
				) {
					return $return;
				}

				$data = $Microservices->returnResults();
				if (
					$Microservices->http === null
					|| $Microservices->http->res === null
				) {
					$status = 200;
				} else {
					$status = $Microservices->http->res->httpStatus;
				}

				return [$headerArr, $data, $status];
			}
		} catch (\Exception $e) {
			if (!in_array(needle: $e->getCode(), haystack: [400, 429])) {
				list($usec, $sec) = explode(separator: ' ', string: microtime());
				$dateTime = date(
					format: 'Y-m-d H:i:s',
					timestamp: $sec
				) . substr(string: $usec, offset: 1);

				// Log request detail
				$logData = [
					'LogType' => 'ERROR',
					'DateTime' => $dateTime,
					'httpReqData' => $Microservices->http->httpReqData,
					'HttpCode' => $e->getCode(),
					'HttpMessage' => $e->getMessage(),
				];

				$logObj = new Log(http: $Microservices->http);
				$logId = $logObj->log(logData: $logData);
			}

			$headerArr = [];
			if ($e->getCode() == 429) {
				$headerArr['Retry-After'] = $e->getMessage();
				$arr = [
					'Message' => 'Too Many request',
					'RetryAfter' => $e->getMessage()
				];
			} elseif(
				isset($logId)
				&& $logId > 0
			) {
				$arr = [
					'Message' => $e->getMessage(),
					'errorLogId' => $logId
				];
			} else {
				$arr = [
					'Message' => $e->getMessage()
				];
			}

			// $dataEncode = new DataEncode(httpReqData: $httpReqData);
			// $dataEncode->init();
			// $dataEncode->startObject();
			// $dataEncode->addKeyData(objectKey: 'Error', data: $arr);

			// $data = $dataEncode->getData();

			if (Env::$OUTPUT_PERFORMANCE_STATS) {
				$performanceData = $Microservices->returnPerformance();
				$errorArr = [
					'Error' => $arr,
					'Status' => $e->getCode(),
					'Stats' => $performanceData['Stats']
				];
			} else {
				$errorArr = ['Error' => $arr];
			}
			$data = json_encode(value: $errorArr);
			$status = $e->getCode();

			return [$headerArr, $data, $status];
		}
	}
}
