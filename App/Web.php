<?php

/**
 * Web
 * php version 8.3
 *
 * @category  Web
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;

/**
 * Web class
 * php version 8.3
 *
 * @category  Web
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Web
{
	/**
	 * Return cURL Config
	 *
	 * @param string $homeURL     Site URL
	 * @param string $method      HTTP method
	 * @param string $route       Route
	 * @param string $queryString Query String
	 * @param array  $header      Header
	 * @param string $payload     Payload
	 *
	 * @return array
	 */
	public static function getCurlConfig(
		$homeURL,
		$method,
		$route,
		$queryString,
		$header = [],
		$payload = '',
		$fileLocation = null
	): array {
		$curlConfig[\CURLOPT_URL] = "{$homeURL}?route={$route}{$queryString}";
		$curlConfig[\CURLOPT_HTTPHEADER] = $header;
		$curlConfig[\CURLOPT_HEADER] = 1;

		switch ($method) {
			case 'GET':
				break;
			case 'POST':
				$curlConfig[\CURLOPT_POST] = true;
				if ($fileLocation === null) {
					$curlConfig[\CURLOPT_POSTFIELDS] = $payload;
				}
				break;
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
				$curlConfig[\CURLOPT_CUSTOMREQUEST] = $method;
				if ($fileLocation === null) {
					$curlConfig[\CURLOPT_POSTFIELDS] = $payload;
				}
				break;
		}
		$curlConfig[\CURLOPT_RETURNTRANSFER] = true;

		$cookieFileName = '/' . md5($homeURL) . '-cookies.txt';
		$cookieFile = Constant::$WEB_COOKIES_DIR . $cookieFileName;
		$curlConfig[\CURLOPT_COOKIEJAR] = $cookieFile; // Store cookies
		$curlConfig[\CURLOPT_COOKIEFILE] = $cookieFile; // Read cookies

		return $curlConfig;
	}

	/**
	 * Trigger cURL
	 *
	 * @param string $homeURL Site URL
	 * @param string $method  HTTP method
	 * @param string $route   Route
	 * @param array  $header  Header
	 * @param string $payload Payload
	 * @param string $fileLocation    File path
	 *
	 * @return mixed
	 */
	public static function trigger(
		$homeURL,
		$method,
		$route,
		$header = [],
		$payload = '',
		$fileLocation = null
	): mixed {
		$queryString = '';
		$curl = curl_init();
		$curlConfig = self::getCurlConfig(
			homeURL: $homeURL,
			method: $method,
			route: $route,
			queryString: $queryString,
			header: $header,
			payload: $payload,
			fileLocation: $fileLocation
		);
		if ($fileLocation !== null) {
			switch ($method) {
				case 'POST':
					// // Create a CURLFile object
					// if (function_exists('curl_file_create')) {
					//     $cFile = curl_file_create($fileLocation, mime_content_type($fileLocation), basename($fileLocation));
					//} else {
					//     // Fallback for very old PHP versions (deprecated)
					//     $cFile = '@' . realpath($fileLocation);
					// }
					// $postData = array(
					//     'description' => 'A fileLocation upload test', // Other form fields go here
					//     'file' => $cFile // This name must match what your server expects
					// );
					// $curlConfig[\CURLOPT_POSTFIELDS] = $postData;
					$curlFile = new \CURLFile($fileLocation, 'text/plain', 'uploaded_file.txt');
					$curlConfig[\CURLOPT_POSTFIELDS] = [
						'file' => $curlFile
					];
					break;
				case 'PUT':
				case 'PATCH':
				case 'DELETE':
					$fp = fopen($fileLocation, 'rb');
					$curlConfig[\CURLOPT_INFILE] = $fp;
					$curlConfig[\CURLOPT_INFILESIZE] = filesize($fileLocation);
					break;
			}
		}
		curl_setopt_array(handle: $curl, options: $curlConfig);

		$curlResponse = curl_exec(handle: $curl);

		$responseHttpCode = curl_getinfo(
			handle: $curl,
			option: \CURLINFO_HTTP_CODE
		);

		$responseContentType = curl_getinfo(
			handle: $curl,
			option: \CURLINFO_CONTENT_TYPE
		);

		$headerSize = curl_getinfo(handle: $curl, option: \CURLINFO_HEADER_SIZE);

		$responseHeaderArr = self::httpParseHeaders(
			rawHeaderArr: substr(
				string: $curlResponse,
				offset: 0,
				length: $headerSize
			)
		);
		$responseBody = substr(string: $curlResponse, offset: $headerSize);

		$queryString = empty($queryString) ? '' : '&' . $queryString;
		$return['request'] = [
			'URI' => htmlspecialchars(string: "{$homeURL}?route={$route}{$queryString}"),
			'httpMethod' => $method,
			'requestHeaders' => $curlConfig[\CURLOPT_HTTPHEADER],
			'requestPayload' => nl2br(htmlspecialchars(string: $payload)),
		];

		$return['response'] = [
			'responseHttpCode' => $responseHttpCode,
			'responseHeaderArr' => $responseHeaderArr,
			'responseContentType' => $responseContentType,
			'responseBody' => $responseBody
		];

		if ($curlResponse === false) {
			$errorCode = curl_errno(handle: $curl);
			$errorMessage = curl_error(handle: $curl);

			$errorConstant = [];

			$list   = get_defined_constants(true);
			$list   = preg_grep('/^CURLE_/', array_flip($list['curl']));

			foreach ($list as $const) {
				if (constant($const) === $errorCode) {
					$errorConstant[] = $const;
				}
			}

			$return['response']['errorCode'] = $errorCode;
			$return['response']['errorMessage'] = $errorMessage;
			$return['response']['errorConstant'] = $errorConstant;
		} else {
			if (
				strpos(
					haystack: $responseContentType,
					needle: 'application/json;'
				) !== false
				&& (
					(strpos(haystack: $responseBody, needle: '[') === 0)
					|| (strpos(haystack: $responseBody, needle: '{') === 0)
				)
			) {
				$response = json_decode(json: $responseBody, associative: true);
			} else {
				$response = $responseBody;
			}

			$return['response']['responseBody'] = $response;
		}
		curl_close(handle: $curl);

		if (
			isset($return['response']['responseBody'])
			&& !is_array($return['response']['responseBody'])
		) {
			$startArrayPos = strpos($return['response']['responseBody'], '[');
			$startObjectPos = strpos($return['response']['responseBody'], '{');
			$startXmlPos = strpos($return['response']['responseBody'], '<');
			if (
				$startArrayPos === 0
				|| $startObjectPos === 0
			) {
				$return['response']['responseBody'] = json_decode(
					json: $return['response']['responseBody'],
					associative: true
				);
			} elseif($startXmlPos === 0) {
				$return['response']['responseBody'] = htmlspecialchars(
					string: $return['response']['responseBody']
				);
			}
		}

		return $return;
	}

	/**
	 * Generates raw header into array
	 *
	 * @param string $rawHeaderArr Raw header from cURL response
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function httpParseHeaders($rawHeaderArr): array
	{
		$headerArr = [];
		$headerName = '';

		foreach (explode(separator: "\n", string: $rawHeaderArr) as $i => $h) {
			$h = explode(separator: ':', string: $h, limit: 2);

			if (isset($h[1])) {
				if (!isset($headerArr[$h[0]])) {
					$headerArr[$h[0]] = trim(string: $h[1]);
				} elseif (is_array(value: $headerArr[$h[0]])) {
					$headerArr[$h[0]] = array_merge(
						$headerArr[$h[0]],
						[trim(string: $h[1])]
					);
				} else {
					$headerArr[$h[0]] = array_merge(
						[$headerArr[$h[0]]],
						[trim(string: $h[1])]
					);
				}

				$headerName = $h[0];
			} else {
				if (substr(string: $h[0], offset: 0, length: 1) == "\t") {
					$headerArr[$headerName] .= "\r\n\t" . trim(string: $h[0]);
				} elseif (!$headerName) {
					$headerArr[0] = trim(string: $h[0]);
				}
			}
		}

		return $headerArr;
	}

	/**
	 * Generates XML Payload
	 *
	 * @param array $xmlParamArr     Xml param's
	 * @param array $payload         Payload
	 * @param bool  $rowTagStartFlag Flag
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function genXmlPayload(&$xmlParamArr, &$payload, $rowTagStartFlag = false): void
	{
		if (empty($xmlParamArr)) {
			return;
		}

		$rowTagStartFlag = false;

		$isObject = (isset($xmlParamArr[0])) ? false : true;

		if (
			!$isObject
			&& count(value: $xmlParamArr) === 1
		) {
			$xmlParamArr = $xmlParamArr[0];
			if (empty($xmlParamArr)) {
				return;
			}
			$isObject = true;
		}

		if (!$isObject) {
			$payload .= '<Rows>';
			$rowTagStartFlag = true;
		}

		if ($rowTagStartFlag) {
			$payload .= '<Row>';
		}
		foreach ($xmlParamArr as $column => &$value) {
			if ($isObject) {
				$payload .= "<{$column}>";
			}
			if (is_array(value: $value)) {
				$_xmlParamArr = $value;
				self::genXmlPayload(xmlParamArr: $_xmlParamArr, payload: $payload, rowTagStartFlag: $rowTagStartFlag);
			} else {
				$payload .= htmlspecialchars(string: $value);
			}
			if ($isObject) {
				$payload .= "</{$column}>";
			}
		}
		if ($rowTagStartFlag) {
			$payload .= '</Row>';
		}
		if (!$isObject) {
			$payload .= '</Rows>';
		}
	}
}
