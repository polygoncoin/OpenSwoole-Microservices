<?php
namespace Microservices\App;

/**
 * Rate Limiter
 *
 * This class is built to handle Limit Rate of requests
 *
 * @category   Rate Limiter
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class RateLimiter
{
    /**
     * Cache connection
     *
     * @var null|\Redis
     */
    private $redis = null;

    /**
     * Current timestamp
     *
     * @var null|integer
    */
    private $currentTimestamp = null;

    /**
     * Constructor
     *
     * @param Redis  $redis
     * @param string $prefix
     * @param int    $maxRequests
     * @param int    $secondsWindow
     * @return void
     */
    public function __construct()
    {
        if (!extension_loaded('redis')) {
            throw new \Exception("Unable to find Redis extension", HttpStatus::$InternalServerError);
        }

        $this->redis = new \Redis();
        $this->redis->connect(getenv('RateLimiterHost'), (int)getenv('RateLimiterHostPort'));

        $this->currentTimestamp = time();
    }

    /**
     * Check the request is valid
     *
     * @param string $key
     * @return array
     * @throws \Exception
     */
    public function check(
        $prefix,
        $maxRequests,
        $secondsWindow,
        $key
    ) {
        $maxRequests = (int)$maxRequests;
        $secondsWindow = (int)$secondsWindow;

        $remainder = $this->currentTimestamp % $secondsWindow;
        $remainder = $remainder !== 0 ? $remainder : $secondsWindow;

        $key = $prefix . $key;

        if ($this->redis->exists($key)) {
            $requestCount = (int)$this->redis->get($key);
        } else {
            $requestCount = 0;
            $this->redis->set($key, $requestCount, $remainder);
        }
        $requestCount++;

        $allowed = $requestCount <= $maxRequests;
        $remaining = max(0, $maxRequests - $requestCount);
        $resetAt = $this->currentTimestamp + $remainder;

        if ($allowed) {
            $this->redis->incr($key);
        }

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'resetAt' => $resetAt
        ];
    }
}
