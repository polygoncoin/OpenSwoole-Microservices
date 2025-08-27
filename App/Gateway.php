<?php
/**
 * Gateway
 * php version 8.3
 *
 * @category  Gateway
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;
use Microservices\App\RouteParser;

/**
 * Gateway - contains checks like IP and Rate Limiting functions
 * php version 8.3
 *
 * @category  Gateway
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Gateway extends RouteParser
{
    /**
     * Rate Limiter
     *
     * @var null|RateLimiter
     */
    private $_rateLimiter = null;

    /**
     * Initialize Gateway
     *
     * @return void
     */
    public function initGateway(): void
    {
        $this->loadClientDetails();

        if (!$this->open) {
            $this->auth->loadUserDetails();
            $this->auth->checkRemoteIp();
        }
        $this->_checkRateLimits();
    }

    /**
     * Check Rate Limits
     *
     * @return void
     */
    private function _checkRateLimits(): void
    {
        $this->_rateLimiter = new RateLimiter();

        $rateLimitChecked = false;

        // Client Rate Limiting
        if (!empty($this->clientDetails['rateLimiterMaxRequests'])
            && !empty($this->clientDetails['rateLimiterSecondsWindow'])
        ) {
            $rateLimiterClientPrefix = getenv(name: 'rateLimiterClientPrefix');
            $rateLimiterMaxRequests = $this->clientDetails['rateLimiterMaxRequests'];
            $rateLimiterSecondsWindow =
                $this->clientDetails['rateLimiterSecondsWindow'];
            $key = $this->clientDetails['client_id'];

            $rateLimitChecked = $this->checkRateLimit(
                rateLimiterPrefix: $rateLimiterClientPrefix,
                rateLimiterMaxRequests: $rateLimiterMaxRequests,
                rateLimiterSecondsWindow: $rateLimiterSecondsWindow,
                key: $key
            );
        }

        if (!$this->open) {
            // Group Rate Limiting
            if (!empty($this->groupDetails['rateLimiterMaxRequests'])
                && !empty($this->groupDetails['rateLimiterSecondsWindow'])
            ) {
                $rateLimiterGroupPrefix =
                    getenv(name: 'rateLimiterGroupPrefix');
                $rateLimiterMaxRequests =
                    $this->groupDetails['rateLimiterMaxRequests'];
                $rateLimiterSecondsWindow =
                    $this->groupDetails['rateLimiterSecondsWindow'];
                $key = $this->clientDetails['client_id'] . ':' .
                    $this->userDetails['group_id'];

                $rateLimitChecked = $this->checkRateLimit(
                    rateLimiterPrefix: $rateLimiterGroupPrefix,
                    rateLimiterMaxRequests: $rateLimiterMaxRequests,
                    rateLimiterSecondsWindow: $rateLimiterSecondsWindow,
                    key: $key
                );
            }

            // User Rate Limiting
            if (!empty($this->userDetails['rateLimiterMaxRequests'])
                && !empty($this->userDetails['rateLimiterSecondsWindow'])
            ) {
                $rateLimiterUserPrefix = getenv(name: 'rateLimiterUserPrefix');
                $rateLimiterMaxRequests =
                    $this->groupDetails['rateLimiterMaxRequests'];
                $rateLimiterSecondsWindow =
                    $this->groupDetails['rateLimiterSecondsWindow'];
                $key = $this->clientDetails['client_id'] . ':' .
                    $this->userDetails['group_id'] . ':' .
                    $this->userDetails['user_id'];

                $rateLimitChecked = $this->checkRateLimit(
                    rateLimiterPrefix: $rateLimiterUserPrefix,
                    rateLimiterMaxRequests: $rateLimiterMaxRequests,
                    rateLimiterSecondsWindow: $rateLimiterSecondsWindow,
                    key: $key
                );
            }
        }

        // Rate limit open traffic (not limited by allowed IPs/CIDR and allowed
        // Rate Limits to users)
        if ($this->cidrChecked === false && $rateLimitChecked === false) {
            $RateLimiterIPPrefix = getenv(name: 'RateLimiterIPPrefix');
            $RateLimiterIPMaxRequests = getenv(name: 'RateLimiterIPMaxRequests');
            $RateLimiterIPSecondsWindow = getenv(name: 'RateLimiterIPSecondsWindow');
            $key = $this->REMOTE_ADDR;

            $this->checkRateLimit(
                rateLimiterPrefix: $RateLimiterIPPrefix,
                rateLimiterMaxRequests: $RateLimiterIPMaxRequests,
                rateLimiterSecondsWindow: $RateLimiterIPSecondsWindow,
                key: $key
            );
        }
    }

    /**
     * Check Rate Limit
     *
     * @param string $rateLimiterPrefix        Prefix
     * @param int    $rateLimiterMaxRequests   Max request
     * @param int    $rateLimiterSecondsWindow Window in seconds
     * @param string $key                      Key
     *
     * @return void
     * @throws \Exception
     */
    public function checkRateLimit(
        $rateLimiterPrefix,
        $rateLimiterMaxRequests,
        $rateLimiterSecondsWindow,
        $key
    ): bool {
        try {
            $result = $this->_rateLimiter->check(
                prefix: $rateLimiterPrefix,
                maxRequests: $rateLimiterMaxRequests,
                secondsWindow: $rateLimiterSecondsWindow,
                key: $key
            );

            if ($result['allowed']) {
                // Process the request
                return true;
            } else {
                // Return 429 Too Many Requests
                throw new \Exception(
                    message: $result['resetAt'] - time(),
                    code: HttpStatus::$TooManyRequests
                );
            }

        } catch (\Exception $e) {
            // Handle connection errors
            throw new \Exception(
                message: $e->getMessage(),
                code: $e->getCode()
            );
        }
    }
}
