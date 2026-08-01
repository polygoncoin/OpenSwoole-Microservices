<?php

/**
 * Rate Limiter
 * php version 8.3
 * 
 * @category  RateLimiter
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Rate Limiter
 * php version 8.3
 * 
 * @category  RateLimiter
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RateLimiter
{
	/**
	 * Cache object
	 * 
	 * @var null|object
	 */
	private $cacheObject = null;

	/**
	 * Constructor
	 * 
	 * @param object $cacheObject
	 */
	public function __construct(
		&$cacheObject
	) {
		$this->cacheObject = &$cacheObject;
	}

	/**
	 * Check rate limit is valid
	 * 
	 * @param string $rateLimitPrefix           Prefix
	 * @param int    $rateLimitMaxRequest       Max request
	 * @param int    $rateLimitMaxRequestWindow Window in seconds
	 * @param string $rateLimitKey              Rate Limit Key
	 * 
	 * @return array
	 */
	public function check(
		$rateLimitPrefix,
		$rateLimitMaxRequest,
		$rateLimitMaxRequestWindow,
		$rateLimitKey
	): array {
		if (
			empty($rateLimitPrefix)
			|| empty($rateLimitMaxRequest)
			|| empty($rateLimitMaxRequestWindow)
			|| empty($rateLimitKey)
		) {
			throw new \Exception(
				message: 'Invalid Rate Limiter Data',
				code: HttpStatus::$InternalServerError
			);
		}

		if ($this->cacheObject === Constant::$NULL) {
			throw new \Exception(
				message: 'Invalid Rate Limiter Cache object',
				code: HttpStatus::$InternalServerError
			);
		}

		$rateLimitMaxRequest = (int)$rateLimitMaxRequest;
		$rateLimitMaxRequestWindow = (int)$rateLimitMaxRequestWindow;

		$remainder = Env::$timestamp % $rateLimitMaxRequestWindow;
		$remainder = $remainder !== 0 ? $remainder : $rateLimitMaxRequestWindow;

		$rateLimitCacheKey = $rateLimitPrefix . $rateLimitKey;

		if (
			$this->cacheObject->cacheExist(
				cacheKey: $rateLimitKey
			)
		) {
			$requestCount = (int)$this->cacheObject->cacheGet(
				cacheKey: $rateLimitCacheKey
			);
		} else {
			$requestCount = 0;
			$this->cacheObject->cacheSet(
				cacheKey: $rateLimitKey,
				cacheValue: $requestCount,
				cacheExpire: $remainder
			);
		}
		$requestCount++;

		$allowed = $requestCount <= $rateLimitMaxRequest;
		$remaining = max(
			0,
			$rateLimitMaxRequest - $requestCount
		);
		$resetOn = Env::$timestamp + $remainder;

		if ($allowed) {
			$this->cacheObject->cacheIncrement(
				cacheKey: $rateLimitKey
			);
		}

		return [
			'allowed' => $allowed,
			'remaining' => $remaining,
			'resetOn' => $resetOn
		];
	}

	/**
	 * Check Rate limit
	 * 
	 * @param string $rateLimitPrefix           Prefix
	 * @param int    $rateLimitMaxRequest       Max request
	 * @param int    $rateLimitMaxRequestWindow Window in seconds
	 * @param string $rateLimitKey              Rate limit key
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function checkRateLimit(
		$rateLimitPrefix,
		$rateLimitMaxRequest,
		$rateLimitMaxRequestWindow,
		$rateLimitKey
	): void {
		if (
			empty($rateLimitPrefix)
			|| empty($rateLimitMaxRequest)
			|| empty($rateLimitMaxRequestWindow)
			|| empty($rateLimitKey)
		) {
			throw new \Exception(
				message: 'Invalid Rate Limiter Data',
				code: HttpStatus::$InternalServerError
			);
		}

		try {
			$result = $this->check(
				rateLimitPrefix: $rateLimitPrefix,
				rateLimitMaxRequest: $rateLimitMaxRequest,
				rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
				rateLimitKey: $rateLimitKey
			);

			if ($result['allowed']) {
				// Process the request
				return;
			} else {
				// Return Too Many request
				throw new \Exception(
					message: $result['resetOn'] - Env::$timestamp,
					code: HttpStatus::$TooManyRequest
				);
			}
		} catch (\Exception $e) {
			// Handle connection errorArray
			throw new \Exception(
				message: $e->getMessage(),
				code: $e->getCode()
			);
		}
	}
}
