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

use Microservices\App\CommonFunction;
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
	 * @param string $httpRequestMethod      HTTP httpRequestMethod
	 * @param string $route       Route
	 * @param string $queryString Query String
	 * @param array  $header      Header
	 * @param string $payload     Payload
	 * 
	 * @return array
	 */
	public static function getCurlConfig(
		$homeURL,
		$httpRequestMethod,
		$route,
		$queryString,
		$header = [],
		$payload = '',
		$fileLocation = null
	): array {
		$curlConfig[\CURLOPT_URL] = "{$homeURL}?route={$route}{$queryString}";
		$curlConfig[\CURLOPT_HTTPHEADER] = $header;
		$curlConfig[\CURLOPT_HEADER] = 1;

		switch ($httpRequestMethod) {
			case Constant::$GET:
				break;
			case Constant::$POST:
				$curlConfig[\CURLOPT_POST] = true;
				if ($fileLocation === Constant::$NULL) {
					$curlConfig[\CURLOPT_POSTFIELDS] = $payload;
				}
				break;
			case Constant::$QUERY:
			case Constant::$PUT:
			case Constant::$PATCH:
			case Constant::$DELETE:
				$curlConfig[\CURLOPT_CUSTOMREQUEST] = $httpRequestMethod;
				if ($fileLocation === Constant::$NULL) {
					$curlConfig[\CURLOPT_POSTFIELDS] = $payload;
				}
				break;
		}
		$curlConfig[\CURLOPT_RETURNTRANSFER] = true;

		$cookieFileName = '/' . md5(
			$homeURL
		) . '-cookies.txt';
		$cookieFile = Constant::$WEB_COOKIES_DIRECTORY . $cookieFileName;
		$curlConfig[\CURLOPT_COOKIEJAR] = $cookieFile; // Store cookies
		$curlConfig[\CURLOPT_COOKIEFILE] = $cookieFile; // Read cookies

		return $curlConfig;
	}

	/**
	 * Trigger cURL
	 * 
	 * @param string $homeURL      Site URL
	 * @param string $httpRequestMethod       HTTP httpRequestMethod
	 * @param string $route        Route
	 * @param array  $header       Header
	 * @param string $payload      Payload
	 * @param string $fileLocation File path
	 * 
	 * @return mixed
	 */
	public static function trigger(
		$homeURL,
		$httpRequestMethod,
		$route,
		$header = [],
		$payload = '',
		$fileLocation = null
	): mixed {
		$queryString = '';
		$curl = curl_init();
		$curlConfig = self::getCurlConfig(
			homeURL: $homeURL,
			httpRequestMethod: $httpRequestMethod,
			route: $route,
			queryString: $queryString,
			header: $header,
			payload: $payload,
			fileLocation: $fileLocation
		);
		if ($fileLocation !== Constant::$NULL) {
			switch ($httpRequestMethod) {
				case Constant::$POST:
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
				case Constant::$QUERY:
				case Constant::$PUT:
				case Constant::$PATCH:
				case Constant::$DELETE:
					$fp = fopen($fileLocation, 'rb');
					$curlConfig[\CURLOPT_INFILE] = $fp;
					$curlConfig[\CURLOPT_INFILESIZE] = filesize($fileLocation);
					break;
			}
		}
		curl_setopt_array(
			handle: $curl,
			options: $curlConfig
		);

		$curlResponse = curl_exec(
			handle: $curl
		);

		$responseHttpCode = curl_getinfo(
			handle: $curl,
			option: \CURLINFO_HTTP_CODE
		);

		$responseContentType = curl_getinfo(
			handle: $curl,
			option: \CURLINFO_CONTENT_TYPE
		);

		$headerSize = curl_getinfo(
			handle: $curl,
			option: \CURLINFO_HEADER_SIZE
		);

		$responseHeaderArray = self::httpParseHeaders(
			rawHeaderArray: substr(
				string: $curlResponse,
				offset: 0,
				length: $headerSize
			)
		);
		$responseBody = substr(
			string: $curlResponse,
			offset: $headerSize
		);

		$queryString = empty($queryString) ? '' : '&' . $queryString;

		$requestPayload = $payload;
		if (!empty($payload)) {
			$isArray = str_starts_with(
				haystack: $payload,
				needle: '['
			);
			$isObject = str_starts_with(
				haystack: $payload,
				needle: '{'
			);
			$isXml = str_starts_with(
				haystack: $payload,
				needle: '<'
			);

			if ($isArray || $isObject) {
				$requestPayload = CommonFunction::jsonDecode(
					value: $payload
				);
			} elseif ($isXml) {
				$requestPayload = htmlspecialchars(
					string: $payload
				);
			}
		}

		$return['HttpRequest'] = [
			'URL' => htmlspecialchars(
				string: "{$homeURL}?route={$route}{$queryString}"
			),
			'Method' => $httpRequestMethod,
			'Headers' => $curlConfig[\CURLOPT_HTTPHEADER]
		];

		switch ($httpRequestMethod) {
			case Constant::$QUERY:
			case Constant::$POST:
			case Constant::$PUT:
			case Constant::$PATCH:
			case Constant::$DELETE:
				$return['HttpRequest']['Payload'] = $requestPayload;
				break;
		}

		$return['HttpResponse'] = [
			'HttpCode' => $responseHttpCode,
			'Headers' => $responseHeaderArray,
			'ContentType' => $responseContentType,
			'ResponseBody' => $responseBody
		];

		if ($curlResponse === Constant::$FALSE) {
			$errorCode = curl_errno(
				handle: $curl
			);
			$errorMessage = curl_error(
				handle: $curl
			);

			$errorConstant = [];

			$list   = get_defined_constants(true);
			$list   = preg_grep('/^CURLE_/', array_flip($list['curl']));

			foreach ($list as $const) {
				if (constant($const) === $errorCode) {
					$errorConstant[] = $const;
				}
			}

			$return['HttpResponse']['errorCode'] = $errorCode;
			$return['HttpResponse']['errorMessage'] = $errorMessage;
			$return['HttpResponse']['errorConstant'] = $errorConstant;
		} else {
			if (
				strpos(
					haystack: $responseContentType,
					needle: 'application/json;'
				) !== Constant::$FALSE
				&& (
					strpos(
						haystack: $responseBody,
						needle: '['
					) === 0
					|| strpos(
						haystack: $responseBody,
						needle: '{'
					) === 0
				)
			) {
				$response = CommonFunction::jsonDecode(
					value: $responseBody
				);
			} else {
				$response = $responseBody;
			}

			$return['HttpResponse']['ResponseBody'] = $response;
		}
		curl_close(
			handle: $curl
		);

		if (
			isset($return['HttpResponse']['ResponseBody'])
			&& !is_array(
				value: $return['HttpResponse']['ResponseBody']
			)
		) {
			$isArray = str_starts_with(
				haystack: $return['HttpResponse']['ResponseBody'],
				needle: '['
			);
			$isObject = str_starts_with(
				haystack: $return['HttpResponse']['ResponseBody'],
				needle: '{'
			);
			$isXml = str_starts_with(
				haystack: $return['HttpResponse']['ResponseBody'],
				needle: '<'
			);
			if ($isArray || $isObject) {
				$return['HttpResponse']['ResponseBody'] = CommonFunction::jsonDecode(
					value: $return['HttpResponse']['ResponseBody']
				);
			} elseif ($isXml) {
				$return['HttpResponse']['ResponseBody'] = htmlspecialchars(
					string: $return['HttpResponse']['ResponseBody']
				);
			}
		}

		return $return;
	}

	/**
	 * Generates raw header into array
	 * 
	 * @param string $rawHeaderArray Raw header from cURL response
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private static function httpParseHeaders(
		$rawHeaderArray
	): array {
		$headerArray = [];
		$headerName = '';

		foreach (
			explode(
				separator: "\n",
				string: $rawHeaderArray
			) as $index => $h
		) {
			$h = explode(
				separator: ':',
				string: $h,
				limit: 2
			);

			if (isset($h[1])) {
				if (!isset($headerArray[$h[0]])) {
					$headerArray[$h[0]] = trim(
						string: $h[1]
					);
				} elseif (
					isset($headerArray[$h[0]])
					&& is_array(
						value: $headerArray[$h[0]]
					)
				) {
					$headerArray[$h[0]] = array_merge($headerArray[$h[0]],
						[
							trim(
								string: $h[1]
							)
						]
					);
				} else {
					$headerArray[$h[0]] = array_merge([$headerArray[$h[0]]],
						[
							trim(
								string: $h[1]
							)
						]
					);
				}

				$headerName = $h[0];
			} else {
				if (
					substr(
						string: $h[0],
						offset: 0,
						length: 1
					) == "\t"
				) {
					$headerArray[$headerName] .= "\r\n\t" . trim(
						string: $h[0]
					);
				} elseif (!$headerName) {
					$headerArray[0] = trim(
						string: $h[0]
					);
				}
			}
		}

		return $headerArray;
	}

	/**
	 * Generates XML Payload
	 * 
	 * @param array $xmlParamArray   Xml param's
	 * @param array $payload         Payload
	 * @param bool  $rowTagStartFlag Flag
	 * 
	 * @return array
	 * @throws \Exception
	 */
	public static function genXmlPayload(
		&$xmlParamArray,
		&$payload,
		$rowTagStartFlag = false
	): void {
		if (empty($xmlParamArray)) {
			return;
		}

		$rowTagStartFlag = false;

		$isObject = (isset($xmlParamArray[0])) ? Constant::$FALSE : Constant::$TRUE;

		if (
			!$isObject
			&& count(
				value: $xmlParamArray
			) === 1
		) {
			$xmlParamArray = $xmlParamArray[0];
			if (empty($xmlParamArray)) {
				return;
			}
			$isObject = true;
		}

		if (!$isObject) {
			$payload .= '<Records>';
			$rowTagStartFlag = true;
		}

		if ($rowTagStartFlag) {
			$payload .= '<Record>';
		}
		foreach ($xmlParamArray as $column => &$value) {
			if ($isObject) {
				$payload .= "<{$column}>";
			}
			if (
				is_array(
					value: $value
				)
			) {
				$_xmlParamArray = $value;
				self::genXmlPayload(
					xmlParamArray: $_xmlParamArray,
					payload: $payload,
					rowTagStartFlag: $rowTagStartFlag
				);
			} else {
				$payload .= htmlspecialchars(
					string: $value
				);
			}
			if ($isObject) {
				$payload .= "</{$column}>";
			}
		}
		if ($rowTagStartFlag) {
			$payload .= '</Record>';
		}
		if (!$isObject) {
			$payload .= '</Records>';
		}
	}
}
