<?php

/**
 * Start
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

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Log;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\HttpStatus;
use Microservices\App\Microservices;

class Start
{
	/**
	 * Process HTTP request data
	 *
	 * @param array $httpReqData HTTP request detail
	 *
	 * @return array
	 */
	public static function http(&$httpReqData)
	{
		$headerArr = [];

		try {
			$Microservices = new Microservices(httpReqData: $httpReqData);

			if (
				$httpReqData['streamData']
				&& $httpReqData['server']['httpMethod'] == 'OPTIONS'
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
					'sData' => $Microservices->http->req->s
				];
				$logsObj = new Log();
				$logsObj->log(logData: $logData);
			}

			$headerArr = [];
			if ($e->getCode() == 429) {
				$headerArr['Retry-After'] = $e->getMessage();
				$arr = [
					'Status' => $e->getCode(),
					'Message' => 'Too Many request',
					'RetryAfter' => $e->getMessage()
				];
			} else {
				$arr = [
					'Status' => $e->getCode(),
					'Message' => $e->getMessage()
				];
			}

			// $dataEncode = new DataEncode(httpReqData: $httpReqData);
			// $dataEncode->init();
			// $dataEncode->startObject();
			// $dataEncode->addKeyData(objectKey: 'Error', data: $arr);

			// $data = $dataEncode->getData();
			$data = json_encode(value: ['Error' => $arr]);
			$status = $e->getCode();

			return [$headerArr, $data, $status];
		}
	}
}
