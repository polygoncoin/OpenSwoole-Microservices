<?php

/**
 * Common Function File
 * php version 8.3
 * 
 * @category  Common Function
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheServerKey;
use Microservices\App\Constant;
use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\Server\CacheServer\CacheServerInterface;

/**
 * Common Function File
 * php version 8.3
 * 
 * @category  Common Function
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class CommonFunction
{
	/**
	 * Check Feature is Enabled (Yes/No)
	 * 
	 * @param Http   $httpObject
	 * @param string $feature
	 * 
	 * @return bool
	 */
	public static function isEnabled(
		&$httpObject,
		$feature
	): bool {
		if (!isset($httpObject->httpRequestObject->activeRequestData['customerData'][$feature])) {
			throw new \Exception(
				message: "Provided feature '{$feature}' not found",
				code: HttpStatus::$InternalServerError
			);
		}
		if (empty($httpObject->httpRequestObject->activeRequestData['customerData'][$feature])) {
			return false;
		} else {
			return ($httpObject->httpRequestObject->activeRequestData['customerData'][$feature] === Constant::$YES) ? Constant::$TRUE : Constant::$FALSE;
		}
	}

	/**
	 * Check Errors related to File Upload
	 * 
	 * @param array $httpFileArray $httpReqData['files']
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public static function validateFileUpload(
		$httpFileArray
	): void {
		if (
			count(
				value: $httpFileArray
			) > 1
		) {
			throw new \Exception(
				message: 'Supports only one file with each request',
				code: HttpStatus::$BadRequest
			);
		}

		foreach ($httpFileArray as $file => $detail) {
			if (isset($detail['error'])) {
				switch ($detail['error']) {
					case \UPLOAD_ERR_INI_SIZE: // value 1
						throw new \Exception(
							message: 'Size of the uploaded file exceeds the maximum value specified',
							code: HttpStatus::$InternalServerError
						);
						break;

					case \UPLOAD_ERR_FORM_SIZE: // value 2
						throw new \Exception(
							message: 'Size of the uploaded file exceeds the maximum value specified in the HTML form in the MAX_FILE_SIZE element',
							code: HttpStatus::$BadRequest
						);
						break;

					case \UPLOAD_ERR_PARTIAL: // value 3
						throw new \Exception(
							message: 'The file was only partially uploaded',
							code: HttpStatus::$InternalServerError
						);
						break;

					case \UPLOAD_ERR_NO_FILE: // value 4
						throw new \Exception(
							message: 'No file was uploaded',
							code: HttpStatus::$InternalServerError
						);
						break;

					case \UPLOAD_ERR_NO_TMP_DIRECTORY: // value 6
						throw new \Exception(
							message: 'No temporary directory is specified',
							code: HttpStatus::$InternalServerError
						);
						break;

					case \UPLOAD_ERR_CANT_WRITE: // value 7
						throw new \Exception(
							message: 'Writing the file to disk failed',
							code: HttpStatus::$InternalServerError
						);
						break;

					case \UPLOAD_ERR_EXTENSION: // value 8
						throw new \Exception(
							message: 'An extension stopped the file upload process',
							code: HttpStatus::$InternalServerError
						);
						break;
				}
			}
		}
	}

	/**
	 * Returns start and end IP number for a given CIDR
	 * 
	 * @param string $cidrString IP address range in CIDR notation for check
	 * 
	 * @return array
	 */
	public static function cidrStringIpNumberRange(
		$cidrString
	): array {
		$response = [];

		if (empty($cidrString)) {
			return $response;
		}

		foreach (
			explode(
				separator: ',',
				string: str_replace(
					search: ' ',
					replace: '',
					subject: $cidrString
				)
			) as $cidr
		) {
			$cidr = trim($cidr);
			if (
				empty($cidr)
				|| $cidr === '0.0.0.0/0'
			) {
				continue;
			}
			if (
				strpos(
					haystack: $cidr,
					needle: '/'
				)
			) {
				[$cidrIp, $bits] = explode(
					separator: '/',
					string: str_replace(
						search: ' ',
						replace: '',
						subject: $cidr
					)
				);
				$binCidrIpStr = str_pad(
					string: decbin(
						num: ip2long(
							ip: $cidrIp
						)
					),
					length: 32,
					pad_string: 0,
					pad_type: STR_PAD_LEFT
				);
				$startIpNumber = bindec(
					binary_string: str_pad(
						string: substr(
							string: $binCidrIpStr,
							offset: 0,
							length: $bits
						),
						length: 32,
						pad_string: 0,
						pad_type: STR_PAD_RIGHT
					)
				);
				$endIpNumber = $startIpNumber + pow(
					num: 2,
					exponent: $bits
				) - 1;
				$response[] = [
					'start' => $startIpNumber,
					'end' => $endIpNumber
				];
			} else {
				if (
					$ipNumber = ip2long(
						ip: $cidr
					)
				) {
					$response[] = [
						'start' => $ipNumber,
						'end' => $ipNumber
					];
				}
			}
		}

		return $response;
	}

	/**
	 * Check IP with CIDR based on cache key containing start and end IP number
	 * 
	 * @param CacheServerInterface $cacheObject  Cache Server object
	 * @param string               $ip           Request Ip
	 * @param string               $cidrCacheKey Cache Key(s)
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public static function checkCacheCidr(
		$cacheObject,
		$ip,
		$cidrCacheKey
	): void {
		if (
			!$cacheObject->cacheExist(
				cacheKey: $cidrCacheKey
			)
		) {
			return;
		}

		$cidrIpNumberRangeArray = $cacheObject->cacheGet(
			cacheKey: $cidrCacheKey
		);
		$isValidIp = self::belongsToCidrIpNumberRange(
			ip: $ip,
			cidrIpNumberRangeArray: $cidrIpNumberRangeArray
		);
		if (!$isValidIp) {
			throw new \Exception(
				message: 'IP not supported',
				code: HttpStatus::$BadRequest
			);
		}
	}

	/**
	 * Check IP with CIDR
	 * 
	 * @param string $ip         Request Ip
	 * @param string $cidrString CIDRs
	 * 
	 * @return null|bool
	 * @throws \Exception
	 */
	public static function checkCidr(
		$ip,
		$cidrString
	): null|bool {
		$isValidIp = true;
		$cidrIpNumberRangeArray = self::cidrStringIpNumberRange(
			cidrString: $cidrString
		);
		if (
			count(
				value: $cidrIpNumberRangeArray
			) > 0
		) {
			$isValidIp = self::belongsToCidrIpNumberRange(
				ip: $ip,
				cidrIpNumberRangeArray: $cidrIpNumberRangeArray
			);
			if (!$isValidIp) {
				throw new \Exception(
					message: 'IP not supported',
					code: HttpStatus::$BadRequest
				);
			}
		}

		return $isValidIp;
	}

	/**
	 * Belongs to Cidr IP number range
	 * 
	 * @param string $ip                     IP Address
	 * @param array  $cidrIpNumberRangeArray Cidr IP number ranges
	 * 
	 * @return bool
	 */
	public static function belongsToCidrIpNumberRange(
		$ip,
		$cidrIpNumberRangeArray
	): bool {
		$isValidIp = false;
		if (
			count(
				value: $cidrIpNumberRangeArray
			) === 0
		) {
			return $isValidIp;
		}

		$ipNumber = ip2long(
			ip: $ip
		);

		foreach ($cidrIpNumberRangeArray as $cidrIpNumber) {
			if (
				$cidrIpNumber['start'] === 0
				&& $cidrIpNumber['end'] === 0
			) {
				$isValidIp = true;
				break;
			} elseif (
				$cidrIpNumber['start'] <= $ipNumber
				&& $ipNumber <= $cidrIpNumber['end']
			) {
				$isValidIp = true;
				break;
			}
		}

		return $isValidIp;
	}

	/**
	 * Validate remote IP
	 * 
	 * @param Http $httpObject
	 * 
	 * @return void
	 */
	public static function checkPrivateRequestCidr(
		&$httpObject
	): void {
		if (
			!self::isEnabled(
				httpObject: $httpObject,
				feature: 'customer_enabled_cidr_check'
			)
		) {
			return;
		}

		self::checkCacheCidr(
			cacheObject: DbCommonFunction::$globalCacheServerObject,
			ip: $httpObject->httpReqData['server']['httpRequestIp'],
			cidrCacheKey: CacheServerKey::customerCidr(
				customerId: $httpObject->httpRequestObject->customerId
			)
		);

		if ($httpObject !== Constant::$NULL) {
			self::checkCacheCidr(
				cacheObject: $httpObject->httpRequestObject->customerCacheObject,
				ip: $httpObject->httpReqData['server']['httpRequestIp'],
				cidrCacheKey: CacheServerKey::customerGroupCidr(
					customerId: $httpObject->httpRequestObject->customerId,
					customerUserGroupId: $httpObject->httpRequestObject->customerUserGroupId
				)
			);

			self::checkCacheCidr(
				cacheObject: $httpObject->httpRequestObject->customerCacheObject,
				ip: $httpObject->httpReqData['server']['httpRequestIp'],
				cidrCacheKey: CacheServerKey::customerUserCidr(
					customerId: $httpObject->httpRequestObject->customerId,
					customerUserId: $httpObject->httpRequestObject->customerUserId
				)
			);
		}
	}

	/**
	 * JSON Decode
	 * 
	 * @param mixed $value
	 * 
	 * @return mixed
	 */
	public static function jsonDecode(
		$value
	): mixed {
		$isArray = str_starts_with(
			haystack: $value,
			needle: '['
		);
		$isObject = str_starts_with(
			haystack: $value,
			needle: '{'
		);

		if ($isArray || $isObject) {
			$value = json_decode(
				json: $value,
				associative: Constant::$TRUE
			);
		}

		return $value;
	}
}
