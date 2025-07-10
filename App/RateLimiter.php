<?php
/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RateLimiter
{
    /**
     * Cache connection
     *
     * @var null|\Redis
     */
    private $_redis = null;

    /**
     * Current timestamp
     *
     * @var null|int
     */
    private $_currentTimestamp = null;

    /**
     * Constructor
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (!extension_loaded(extension: 'redis')) {
            throw new \Exception(
                message: "Unable to find Redis extension",
                code: HttpStatus::$InternalServerError
            );
        }

        $this->_redis = new \Redis();
        $this->_redis->connect(
            getenv(name: 'RateLimiterHost'),
            (int)getenv(name: 'RateLimiterHostPort')
        );

        $this->_currentTimestamp = time();
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
        $maxRequests = (int)$maxRequests;
        $secondsWindow = (int)$secondsWindow;

        $remainder = $this->_currentTimestamp % $secondsWindow;
        $remainder = $remainder !== 0 ? $remainder : $secondsWindow;

        $key = $prefix . $key;

        if ($this->_redis->exists($key)) {
            $requestCount = (int)$this->_redis->get($key);
        } else {
            $requestCount = 0;
            $this->_redis->set($key, $requestCount, $remainder);
        }
        $requestCount++;

        $allowed = $requestCount <= $maxRequests;
        $remaining = max(0, $maxRequests - $requestCount);
        $resetAt = $this->_currentTimestamp + $remainder;

        if ($allowed) {
            $this->_redis->incr($key);
        }

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'resetAt' => $resetAt
        ];
    }
}
