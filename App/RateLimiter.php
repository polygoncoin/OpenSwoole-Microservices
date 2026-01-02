<?php

/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Env;
use Microservices\App\DbFunctions;

/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
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
    public $cache = null;

    /**
     * Constructor
     *
     * @param HttpRequest $req HTTP Request object
     */
    public function __construct()
    {
        if (((int)getenv(name: 'enableRateLimiting')) === 0) {
            return;
        }

        $rateLimitServerType = getenv(name: 'rateLimitServerType');
        $rateLimitServerHostname = getenv(name: 'rateLimitServerHostname');
        $rateLimitServerPort = getenv(name: 'rateLimitServerPort');

        $this->cache = DbFunctions::connectCache(
            cacheServerType: $rateLimitServerType,
            cacheHostname: $rateLimitServerHostname,
            cachePort: $rateLimitServerPort,
            cacheUsername: '',
            cachePassword: '',
            cacheDatabase: '',
            cacheTable: ''
        );
    }

    /**
     * Check the request is valid
     *
     * @param string $prefix        Prefix
     * @param int    $maxRequests   Max request
     * @param int    $secondsWindow Window in seconds
     * @param string $key           Key
     *
     * @return array
     */
    public function check(
        $prefix,
        $maxRequests,
        $secondsWindow,
        $key
    ): array {
        if (
            $this->cache === null
            && (((int)getenv(name: 'enableRateLimiting')) === 0)
        ) {
            return [
                'allowed' => true,
                'remaining' => 1,
                'resetAt' => 1
            ];
        }

        $maxRequests = (int)$maxRequests;
        $secondsWindow = (int)$secondsWindow;

        $remainder = Env::$timestamp % $secondsWindow;
        $remainder = $remainder !== 0 ? $remainder : $secondsWindow;

        $key = $prefix . $key;

        if ($this->cache->cacheExists($key)) {
            $requestCount = (int)$this->cache->getCache($key);
        } else {
            $requestCount = 0;
            $this->cache->setCache($key, $requestCount, $remainder);
        }
        $requestCount++;

        $allowed = $requestCount <= $maxRequests;
        $remaining = max(0, $maxRequests - $requestCount);
        $resetAt = Env::$timestamp + $remainder;

        if ($allowed) {
            $this->cache->incrementCache($key);
        }

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'resetAt' => $resetAt
        ];
    }
}
