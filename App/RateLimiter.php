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
	 * Caching object
	 *
	 * @var null|Object
	 */
	public $cacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param HttpRequest $req HTTP Request object
	 */
	public function __construct()
	{
		if (!Env::$enableRateLimiting) {
			return;
		}

		$this->cacheServerObj = DbCommonFunction::connectCacheServer(
			cacheServerType: Env::$rateLimitServerType,
			cacheServerHostname: Env::$rateLimitServerHostname,
			cacheServerPort: Env::$rateLimitServerPort,
			cacheServerUsername: '',
			cacheServerPassword: '',
			cacheServerDB: '',
			cacheServerTable: ''
		);
	}

	/**
	 * Check the request is valid
	 *
	 * @param string $prefix        Prefix
	 * @param int    $maxRequest   Max request
	 * @param int    $secondsWindow Window in seconds
	 * @param string $key           Key
	 *
	 * @return array
	 */
	public function check(
		$prefix,
		$maxRequest,
		$secondsWindow,
		$key
	): array {
		if (
			$this->cacheServerObj === null
			&& (!Env::$enableRateLimiting)
		) {
			return [
				'allowed' => true,
				'remaining' => 1,
				'resetAt' => 1
			];
		}

		$maxRequest = (int)$maxRequest;
		$secondsWindow = (int)$secondsWindow;

		$remainder = Env::$timestamp % $secondsWindow;
		$remainder = $remainder !== 0 ? $remainder : $secondsWindow;

		$key = $prefix . $key;

		if ($this->cacheServerObj->cacheExists($key)) {
			$requestCount = (int)$this->cacheServerObj->getCache($key);
		} else {
			$requestCount = 0;
			$this->cacheServerObj->setCache($key, $requestCount, $remainder);
		}
		$requestCount++;

		$allowed = $requestCount <= $maxRequest;
		$remaining = max(0, $maxRequest - $requestCount);
		$resetAt = Env::$timestamp + $remainder;

		if ($allowed) {
			$this->cacheServerObj->incrementCache($key);
		}

		return [
			'allowed' => $allowed,
			'remaining' => $remaining,
			'resetAt' => $resetAt
		];
	}
}
