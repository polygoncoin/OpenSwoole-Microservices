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
use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
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
	 * Validate remote IP
	 *
	 * @param Http   $http
	 * @param string $feature
	 *
	 * @return bool
	 */
	public static function isEnabled(&$http, $feature): bool
	{
		return ($http->req->s['customerData'][$feature] === 'Yes') ? true : false;
	}

	/**
	 * Check Errors related to File Upload
	 *
	 * @param array $httpFileArr $httpReqData['files']
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function validateFileUpload($httpFileArr): void
	{
		if (count($httpFileArr) > 1) {
			throw new \Exception(
				message: 'Supports only one file with each request',
				code: HttpStatus::$BadRequest
			);
		}

		foreach ($httpFileArr as $file => $detail) {
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

					case \UPLOAD_ERR_NO_TMP_DIR: // value 6
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
	public static function cidrStringIpNumberRange($cidrString): array
	{
		$response = [];

		foreach (
			explode(
				separator: ', ',
				string: str_replace(
					search: ' ',
					replace: '',
					subject: $cidrString
				)
			) as $cidr
		) {
			if (strpos(haystack: $cidr, needle: '/')) {
				[$cidrIp, $bits] = explode(
					separator: '/',
					string: str_replace(search: ' ', replace: '', subject: $cidr)
				);
				$binCidrIpStr = str_pad(
					string: decbin(num: ip2long(ip: $cidrIp)),
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
				$endIpNumber = $startIpNumber + pow(num: 2, exponent: $bits) - 1;
				$response[] = [
					'start' => $startIpNumber,
					'end' => $endIpNumber
				];
			} else {
				if ($ipNumber = ip2long(ip: $cidr)) {
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
	 * @param CacheServerInterface $cacheObj     Cache Server object
	 * @param string               $IP           Request Ip
	 * @param string               $cidrCacheKey Cache Key(s)
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function checkCacheCidr($cacheObj, $IP, $cidrCacheKey): void
	{
		if (!$cacheObj->cacheExist(cacheKey: $cidrCacheKey)) {
			return;
		}

		$cidrIpNumberRangeArr = json_decode(
			json: $cacheObj->cacheGet(
				cacheKey: $cidrCacheKey
			),
			associative: true
		);
		$isValidIp = self::belongsToCidrIpNumberRange(IP: $IP, cidrIpNumberRangeArr: $cidrIpNumberRangeArr);
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
	 * @param string $IP         Request Ip
	 * @param string $cidrString CIDRs
	 *
	 * @return null|bool
	 * @throws \Exception
	 */
	public static function checkCidr($IP, $cidrString): null|bool
	{
		$cidrIpNumberRangeArr = self::cidrStringIpNumberRange(cidrString: $cidrString);
		$isValidIp = self::belongsToCidrIpNumberRange(IP: $IP, cidrIpNumberRangeArr: $cidrIpNumberRangeArr);
		if (!$isValidIp) {
			throw new \Exception(
				message: 'IP not supported',
				code: HttpStatus::$BadRequest
			);
		}

		return $isValidIp;
	}

	/**
	 * Belongs to Cidr IP number range
	 *
	 * @param string $IP                   IP
	 * @param array  $cidrIpNumberRangeArr Cidr IP number ranges
	 *
	 * @return bool
	 */
	public static function belongsToCidrIpNumberRange($IP, $cidrIpNumberRangeArr): bool
	{
		$ipNumber = ip2long(ip: $IP);

		$isValidIp = false;
		foreach ($cidrIpNumberRangeArr as $cidrIpNumber) {
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
	 * @param Http $http
	 *
	 * @return void
	 */
	public static function checkPrivateRequestCidr(&$http): void
	{
		if (!self::isEnabled(http: $http, feature: 'enableCidrCheck')) {
			return;
		}

		self::checkCacheCidr(
			cacheObj: DbCommonFunction::$gCacheServer,
			IP: $http->httpReqData['server']['httpRequestIP'],
			cidrCacheKey: CacheServerKey::customerCidr(
				customerId: $http->req->customerId
			)
		);

		if ($http !== null) {
			self::checkCacheCidr(
				cacheObj: $http->req->clientCacheObj,
				IP: $http->httpReqData['server']['httpRequestIP'],
				cidrCacheKey: CacheServerKey::customerGroupCidr(
					customerId: $http->req->customerId,
					groupId: $http->req->groupId
				)
			);

			self::checkCacheCidr(
				cacheObj: $http->req->clientCacheObj,
				IP: $http->httpReqData['server']['httpRequestIP'],
				cidrCacheKey: CacheServerKey::customerUserCidr(
					customerId: $http->req->customerId,
					userId: $http->req->userId
				)
			);
		}
	}

	/**
	 * Unique HTTP request hash
	 *
	 * @param array $hashArray Hash array
	 *
	 * @return string
	 */
	public static function httpRequestHash($hashArray): string
	{
		return md5(json_encode(value: $hashArray));
	}

	/**
	 * Get request IP
	 *
	 * @return string
	 */
	public static function getHttpRequestIp() {
		// Check for shared internet connections (e.g., Cloudflare, proxy)
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		// Check if the user is behind a proxy and the IP is forwarded
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			// HTTP_X_FORWARDED_FOR can contain a comma-separated list of IPs
			// The first one is typically the original customer IP
			$ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$ip = trim($ipList[0]);
		}
		// Default method: get the remote address directly
		else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}
