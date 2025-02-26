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
     * Limiter prefix
     *
     * @var string
     */
    private $prefix = '';

    /**
     * Max requests
     *
     * @var null|integer
     */
    private $maxRequests = null;

    /**
     * Max requests window in seconds
     *
     * @var null|integer
     */
    private $secondsWindow = null;

    /**
     * Current timestamp
     *
     * @var null|integer
    */
    private $currentTimestamp = null;

    /**
     * Constructor
     */
    public function __construct(
        &$redis,
        $prefix,
        $maxRequests,
        $secondsWindow
    ) {
        $this->redis = &$redis;
        $this->prefix = $prefix;
        $this->maxRequests = (int)$maxRequests;
        $this->secondsWindow = (int)$secondsWindow;

        $this->currentTimestamp = time();
    }

    /**
     * Check the request is valid
     *
     * @param string $key
     * @return array
     * @throws \RuntimeException
     */
    public function check(string $key): array
    {
        $key = $this->prefix . $key;

        $windowStart = $this->currentTimestamp - $this->secondsWindow;

        $this->redis->multi();
        $this->redis->zRemRangeByScore($key, 0, $windowStart);
        $this->redis->zAdd($key, $this->currentTimestamp, (string)microtime(true));
        $this->redis->zCard($key);
        $this->redis->expire($key, $this->secondsWindow);

        $results = $this->redis->exec();

        if ($results === false) {
            throw new \RuntimeException('Rate Limit transaction failed');
        }

        $requestCount = $results[2];
        $allowed = $requestCount <= $this->maxRequests;
        $remaining = max(0, $this->maxRequests - $requestCount);
        $resetAt = $this->currentTimestamp + $this->secondsWindow;

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'resetAt' => $resetAt
        ];
    }
}
