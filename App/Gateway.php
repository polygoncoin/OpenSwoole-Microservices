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

use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Functions;
use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;

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
class Gateway
{
    /**
     * CIDR checked boolean flag
     *
     * @var bool
     */
    public $cidrChecked = false;

    /**
     * Rate Limiter
     *
     * @var null|RateLimiter
     */
    private $rateLimiter = null;

    /**
     * Rate Limit check flag
     *
     * @var bool
     */
    private $rateLimitChecked = false;

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
    }

    /**
     * Initialize Gateway
     *
     * @return void
     */
    public function initGateway(): void
    {
        $this->api->req->loadClientDetails();

        if (!$this->api->req->open) {
            $this->api->req->auth->loadUserDetails();
            $this->checkCidr();
        }
        $this->checkRateLimits();
    }

    /**
     * Check Rate Limits
     *
     * @return void
     */
    private function checkRateLimits(): void
    {
        $this->rateLimiter = new RateLimiter();

        // Client Rate Limiting
        $this->rateLimitClient();

        if (!$this->api->req->open) {
            // Group Rate Limiting
            $this->rateLimitGroup();

            // User Rate Limiting
            $this->rateLimitUser();

            // User Rate Limiting Request Delay
            $this->rateLimitUsersRequest();
        }

        // Rate limit open traffic (not limited by allowed IPs/CIDR and allowed
        // Rate Limits to users)
        if ($this->cidrChecked === false && $this->rateLimitChecked === false) {
            // IP Rate Limiting
            $this->rateLimitIp();
        }
    }

    /**
     * Check Rate Limit
     *
     * @param string $rateLimitPrefix        Prefix
     * @param int    $rateLimitMaxRequests   Max request
     * @param int    $rateLimitMaxRequestsWindow Window in seconds
     * @param string $key                    Key
     *
     * @return void
     * @throws \Exception
     */
    public function checkRateLimit(
        $rateLimitPrefix,
        $rateLimitMaxRequests,
        $rateLimitMaxRequestsWindow,
        $key
    ): bool {
        try {
            $result = $this->rateLimiter->check(
                prefix: $rateLimitPrefix,
                maxRequests: $rateLimitMaxRequests,
                secondsWindow: $rateLimitMaxRequestsWindow,
                key: $key
            );

            if ($result['allowed']) {
                // Process the request
                return true;
            } else {
                // Return 429 Too Many Requests
                throw new \Exception(
                    message: $result['resetAt'] - Env::$timestamp,
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

    /**
     * Validate remote IP
     *
     * @return void
     * @throws \Exception
     */
    public function checkCidr(): void
    {
        if (!Env::$enableCidrChecks) {
            return;
        }

        $cCidrKey = CacheKey::cCidr(
            cID: $this->api->req->s['cDetails']['id']
        );
        $gCidrKey = CacheKey::gCidr(
            gID: $this->api->req->s['uDetails']['group_id']
        );
        $uCidrKey = CacheKey::uCidr(
            cID: $this->api->req->s['cDetails']['id'],
            uID: $this->api->req->s['uDetails']['id']
        );
        foreach ([$cCidrKey, $gCidrKey, $uCidrKey] as $key) {
            if (!$this->cidrChecked) {
                $this->cidrChecked = Functions::checkCacheCidr(
                    IP: $this->api->req->IP,
                    againstCacheKey: $key
                );
            }
        }
    }

    /**
     * Rate Limit Client Request
     *
     * @return void
     */
    private function rateLimitClient(): void
    {
        if (
            !Env::$enableRateLimitAtClientLevel
            || empty($this->api->req->s['cDetails']['rateLimitMaxRequests'])
            || empty($this->api->req->s['cDetails']['rateLimitMaxRequestsWindow'])
        ) {
            return;
        }

        $rateLimitClientPrefix = Env::$rateLimitClientPrefix;
        $rateLimitMaxRequests
            = $this->api->req->s['cDetails']['rateLimitMaxRequests'];
        $rateLimitMaxRequestsWindow
            = $this->api->req->s['cDetails']['rateLimitMaxRequestsWindow'];
        $key = $this->api->req->s['cDetails']['id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitClientPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitMaxRequestsWindow: $rateLimitMaxRequestsWindow,
            key: $key
        );
    }

    /**
     * Rate Limit Client Group Request
     *
     * @return void
     */
    private function rateLimitGroup(): void
    {
        if (
            !Env::$enableRateLimitAtGroupLevel
            || empty($this->api->req->s['gDetails']['rateLimitMaxRequests'])
            || empty($this->api->req->s['gDetails']['rateLimitMaxRequestsWindow'])
        ) {
            return;
        }

        $rateLimitGroupPrefix
            = Env::$rateLimitGroupPrefix;
        $rateLimitMaxRequests
            = $this->api->req->s['gDetails']['rateLimitMaxRequests'];
        $rateLimitMaxRequestsWindow
            = $this->api->req->s['gDetails']['rateLimitMaxRequestsWindow'];
        $key = $this->api->req->s['cDetails']['id'] . ':'
            . $this->api->req->s['uDetails']['id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitGroupPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitMaxRequestsWindow: $rateLimitMaxRequestsWindow,
            key: $key
        );
    }

    /**
     * Rate Limit Client Group User Request
     *
     * @return void
     */
    private function rateLimitUser(): void
    {
        if (
            !Env::$enableRateLimitAtUserLevel
            || empty($this->api->req->s['uDetails']['rateLimitMaxRequests'])
            || empty($this->api->req->s['uDetails']['rateLimitMaxRequestsWindow'])
        ) {
            return;
        }

        $rateLimitUserPrefix = Env::$rateLimitUserPrefix;
        $rateLimitMaxRequests
            = $this->api->req->s['gDetails']['rateLimitMaxRequests'];
        $rateLimitMaxRequestsWindow
            = $this->api->req->s['gDetails']['rateLimitMaxRequestsWindow'];
        $key = $this->api->req->s['cDetails']['id'] . ':'
            . $this->api->req->s['uDetails']['id'] . ':'
            . $this->api->req->s['uDetails']['user_id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitUserPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitMaxRequestsWindow: $rateLimitMaxRequestsWindow,
            key: $key
        );
    }

    /**
     * Rate Limit Client Group User Request Delay
     *
     * @return void
     */
    private function rateLimitUsersRequest(): void
    {
        if (!Env::$enableRateLimitAtUsersRequestLevel) {
            return;
        }

        $rateLimitUserPrefix = Env::$rateLimitUsersRequestPrefix;
        $rateLimitMaxRequests = Env::$rateLimitUsersMaxRequests;
        $rateLimitMaxRequestsWindow = Env::$rateLimitUsersMaxRequestsWindow;
        $key = $this->api->req->s['cDetails']['id'] . ':'
            . $this->api->req->s['uDetails']['id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitUserPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitMaxRequestsWindow: $rateLimitMaxRequestsWindow,
            key: $key
        );
    }

    /**
     * Rate Limit Request from source IP
     *
     * @return void
     */
    private function rateLimitIp(): void
    {
        if (!Env::$enableRateLimitAtIpLevel) {
            return;
        }

        $rateLimitIPPrefix = Env::$rateLimitIPPrefix;
        $rateLimitIPMaxRequests = Env::$rateLimitIPMaxRequests;
        $rateLimitIPMaxRequestsWindow = Env::$rateLimitIPMaxRequestsWindow;
        $key = $this->api->req->IP;

        $this->checkRateLimit(
            rateLimitPrefix: $rateLimitIPPrefix,
            rateLimitMaxRequests: $rateLimitIPMaxRequests,
            rateLimitMaxRequestsWindow: $rateLimitIPMaxRequestsWindow,
            key: $key
        );
    }
}
