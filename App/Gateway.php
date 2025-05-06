<?php
namespace Microservices\App;

use Microservices\App\CacheKey;
use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;
use Microservices\App\RouteParser;

/*
 * Class handling Gateway Checks
 *
 * This class contains Gateway Checks like IP and Rate Limiting functions
 *
 * @category   Gateway Checks
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Gateway extends RouteParser
{
    /**
     * Rate Limiter
     *
     * @var null|RateLimiter
     */
    private $rateLimiter = null;

    /**
     * Initialize Gateway
     *
     * @return void
     */
    public function initGateway()
    {
        $this->loadClientDetails();

        if (!$this->open) {
            $this->loadUserDetails();
            $this->checkRemoteIp();
        }
        $this->checkRateLimits();
    }

    /**
     * Validate request IP
     *
     * @return void
     * @throws \Exception
     */
    public function checkRemoteIp()
    {
        $groupId = $this->userDetails['group_id'];

        $this->cidrKey = CacheKey::CIDR($this->userDetails['group_id']);
        if ($this->cache->cacheExists($this->cidrKey)) {
            $this->cidrChecked = true;
            $cidrs = json_decode($this->cache->getCache($this->cidrKey), true);
            $ipNumber = ip2long($this->REMOTE_ADDR);
            $isValidIp = false;
            foreach ($cidrs as $cidr) {
                if ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                    $isValidIp = true;
                    break;
                }
            }
            if (!$isValidIp) {
                throw new \Exception('IP not supported', HttpStatus::$BadRequest);
            }
        }
    }

    /**
     * Check Rate Limits
     *
     * @return void
     */
    private function checkRateLimits()
    {
        $this->rateLimiter = new RateLimiter();

        $rateLimitChecked = false;

        // Client Rate Limiting
        if (
            !empty($this->clientDetails['rateLimiterMaxRequests'])
            && !empty($this->clientDetails['rateLimiterSecondsWindow'])
        ) {
            $rateLimitChecked = $this-checkRateLimit(
                $RateLimiterGroupPrefix = getenv('RateLimiterClientPrefix'),
                $RateLimiterMaxRequests = $this->clientDetails['rateLimiterMaxRequests'],
                $RateLimiterSecondsWindow = $this->clientDetails['rateLimiterSecondsWindow'],
                $key = $this->clientDetails['client_id']
            );
        }

        if (!$this->open) {
            // Group Rate Limiting
            if (
                !empty($this->groupDetails['rateLimiterMaxRequests'])
                && !empty($this->groupDetails['rateLimiterSecondsWindow'])
            ) {
                $rateLimitChecked = $this-checkRateLimit(
                    $RateLimiterGroupPrefix = getenv('RateLimiterGroupPrefix'),
                    $RateLimiterMaxRequests = $this->groupDetails['rateLimiterMaxRequests'],
                    $RateLimiterSecondsWindow = $this->groupDetails['rateLimiterSecondsWindow'],
                    $key = $this->clientDetails['client_id'] . ':' . $this->userDetails['group_id']
                );
            }

            // User Rate Limiting
            if (
                !empty($this->userDetails['rateLimiterMaxRequests'])
                && !empty($this->userDetails['rateLimiterSecondsWindow'])
            ) {
                $rateLimitChecked = $this->checkRateLimit(
                    $RateLimiterUserPrefix = getenv('RateLimiterUserPrefix'),
                    $RateLimiterMaxRequests = $this->groupDetails['rateLimiterMaxRequests'],
                    $RateLimiterSecondsWindow = $this->groupDetails['rateLimiterSecondsWindow'],
                    $key = $this->clientDetails['client_id'] . ':' . $this->userDetails['group_id'] . ':' . $this->userDetails['user_id']
                );
            }
        }

        // Rate limit open traffic (not limited by allowed IPs/CIDR and allowed Rate Limits to users)
        if ($this->cidrChecked === false && $rateLimitChecked === false) {
            $this->checkRateLimit(
                $RateLimiterIPPrefix = getenv('RateLimiterIPPrefix'),
                $RateLimiterIPMaxRequests = getenv('RateLimiterIPMaxRequests'),
                $RateLimiterIPSecondsWindow = getenv('RateLimiterIPSecondsWindow'),
                $key = $this->REMOTE_ADDR
            );
        }
    }

    /**
     * Check Rate Limit
     *
     * @param string $RateLimiterPrefix
     * @param int    $RateLimiterMaxRequests
     * @param int    $RateLimiterSecondsWindow
     * @param string $key
     * @return void
     * @throws \Exception
     */
    public function checkRateLimit(
        $RateLimiterPrefix,
        $RateLimiterMaxRequests,
        $RateLimiterSecondsWindow,
        $key
    ) {
        try {
            $result = $this->rateLimiter->check(
                $RateLimiterPrefix,
                $RateLimiterMaxRequests,
                $RateLimiterSecondsWindow,
                $key
            );

            if ($result['allowed']) {
                // Process the request
                return true;
            } else {
                // Return 429 Too Many Requests
                throw new \Exception($result['resetAt'] - time(), HttpStatus::$TooManyRequests);
            }

        } catch (\Exception $e) {
            // Handle connection errors
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }
}
