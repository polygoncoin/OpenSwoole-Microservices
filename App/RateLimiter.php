<?php

/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;

/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RateLimiter
{
	/**
	 * HTTP object
	 *
	 * @var null|Http
	 */
	private $http = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;

		if (!Env::$enableRateLimiting) {
			return;
		}

		$this->http->req->clientCacheObj = DbCommonFunction::connectClientCache(
			req: $this->http->req,
			fetchFrom: 'Master'
		);
	}

	/**
	 * Check rate limit is valid
	 *
	 * @param string $prefix        Prefix
	 * @param int    $maxRequest    Max request
	 * @param int    $secondsWindow Window in seconds
	 * @param string $rateLimitKey  Rate Limit Key
	 *
	 * @return array
	 */
	public function check(
		$prefix,
		$maxRequest,
		$secondsWindow,
		$rateLimitKey
	): array {
		if (
			$this->http->req->clientCacheObj === null
			&& (!Env::$enableRateLimiting)
		) {
			return [
				'allowed' => true,
				'remaining' => 1,
				'resetOn' => 1
			];
		}

		$maxRequest = (int)$maxRequest;
		$secondsWindow = (int)$secondsWindow;

		$remainder = Env::$timestamp % $secondsWindow;
		$remainder = $remainder !== 0 ? $remainder : $secondsWindow;

		$rateLimitKey = $prefix . $rateLimitKey;

		if ($this->http->req->clientCacheObj->cacheExist($rateLimitKey)) {
			$requestCount = (int)$this->http->req->clientCacheObj->cacheGet(
				cacheKey: $rateLimitKey
			);
		} else {
			$requestCount = 0;
			$this->http->req->clientCacheObj->cacheSet(
				cacheKey: $rateLimitKey,
				value: $requestCount,
				expire: $remainder
			);
		}
		$requestCount++;

		$allowed = $requestCount <= $maxRequest;
		$remaining = max(0, $maxRequest - $requestCount);
		$resetOn = Env::$timestamp + $remainder;

		if ($allowed) {
			$this->http->req->clientCacheObj->cacheIncrement(cacheKey: $rateLimitKey);
		}

		return [
			'allowed' => $allowed,
			'remaining' => $remaining,
			'resetOn' => $resetOn
		];
	}
}
