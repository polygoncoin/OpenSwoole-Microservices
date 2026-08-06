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
	public static function http(
		&$httpReqData
	): array {
		$headerArray = [];

		if ($httpReqData['server']['httpRequestMethod'] == Constant::$POST) {
			$isArray = str_starts_with(
				haystack: $httpReqData['post'],
				needle: '['
			);
			$isObject = str_starts_with(
				haystack: $httpReqData['post'],
				needle: '{'
			);
			$isXml = str_starts_with(
				haystack: $httpReqData['post'],
				needle: '<'
			);
			if (!$isArray && !$isObject && !$isXml) {
				parse_str(
					$httpReqData['post'],
					$httpReqData['post']
				);
				$httpReqData['post'] = json_encode(
					value: $httpReqData['post']
				);
			}
		}

		try {
			$Microservices = new Microservices(
				httpReqData: $httpReqData
			);

			if (
				$httpReqData['streamData']
				&& $httpReqData['server']['httpRequestMethod'] == Constant::$OPTIONS
			) {
				// Setting CORS
				$headerArray = $Microservices->getHeaders();
				$data = '{}';
				$status = HttpStatus::$Ok;

				return [
					$headerArray,
					$data,
					$status
				];
			}

			if ($Microservices->init()) {
				// Setting CORS
				if ($httpReqData['streamData']) {
					$headerArray = $Microservices->getHeaders();
				}

				$return = $Microservices->process();
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

				$data = $Microservices->returnResults();
				if (
					$Microservices->httpObject === Constant::$NULL
					|| $Microservices->httpObject->httpResponseObject === Constant::$NULL
				) {
					$status = HttpStatus::$Ok;
				} else {
					$status = $Microservices->httpObject->httpResponseObject->httpStatus;
				}

				return [
					$headerArray,
					$data,
					$status
				];
			}
		} catch (\Exception $e) {
			if (
				!in_array(
					needle: $e->getCode(),
					haystack: [HttpStatus::$BadRequest, HttpStatus::$TooManyRequest],
					strict: Constant::$TRUE
				)
			) {
				list(
					$usec,
					$sec
				) = explode(
					separator: ' ',
					string: microtime()
				);
				$dateTime = date(
					format: 'Y-m-d H:i:s',
					timestamp: $sec
				) . substr(
					string: $usec,
					offset: 1
				);

				// Log request detail
				$logData = [
					'LogType' => 'ERROR',
					'DateTime' => $dateTime,
					'httpReqData' => $Microservices->httpObject->httpReqData,
					'HttpCode' => $e->getCode(),
					'HttpMessage' => $e->getMessage(),
				];

				$logObject = new Log(
					httpObject: $Microservices->httpObject
				);
				
				$payload = [];
				if (
					isset($Microservices->httpObject->httpRequestObject)
					&& isset($Microservices->httpObject->httpRequestObject->dataDecodeObject)
				) {
					$payload = $Microservices->httpObject->httpRequestObject->dataDecodeObject->get();
				}
				$logId = $logObject->log(
					logData: $logData,
					payload: $payload
				);
			}

			$headerArray = [];
			if ($e->getCode() == HttpStatus::$TooManyRequest) {
				$headerArray['Retry-After'] = $e->getMessage();
				$arr = [
					'Message' => 'Too Many request',
					'RetryAfter' => $e->getMessage()
				];
			} elseif (
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

			// $dataEncodeObject = new DataEncode(
			// httpReqData: $httpReqData
			// );
			// $dataEncodeObject->init();
			// $dataEncodeObject->startObject();
			// $dataEncodeObject->addKeyData(
			// objectKey: 'Error',
			// data: $arr
			// );

			// $data = $dataEncodeObject->getData();

			if (Env::$OUTPUT_PERFORMANCE_STATS) {
				$performanceData = $Microservices->httpObject->httpResponseObject->returnPerformance();
				$errorArray = [
					'Error' => $arr,
					'Status' => $e->getCode(),
					'Stats' => $performanceData['Stats']
				];
			} else {
				$errorArray = ['Error' => $arr];
			}
			$data = json_encode(
				value: $errorArray
			);
			$status = $e->getCode();

			return [
				$headerArray,
				$data,
				$status
			];
		}
	}
}
