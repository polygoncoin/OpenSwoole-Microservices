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
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\App\HttpRequest;
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
     * @param int    $rateLimitSecondsWindow Window in seconds
     * @param string $key                    Key
     *
     * @return void
     * @throws \Exception
     */
    public function checkRateLimit(
        $rateLimitPrefix,
        $rateLimitMaxRequests,
        $rateLimitSecondsWindow,
        $key
    ): bool {
        try {
            $result = $this->rateLimiter->check(
                prefix: $rateLimitPrefix,
                maxRequests: $rateLimitMaxRequests,
                secondsWindow: $rateLimitSecondsWindow,
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
        if (((int)getenv(name: 'activateCidrChecks')) === 0) {
            return;
        }

        $ipNumber = ip2long(ip: $this->api->req->IP);

        $cCidrKey = CacheKey::cCidr(
            cID: $this->api->req->s['cDetails']['id']
        );
        $gCidrKey = CacheKey::gCidr(
            gID: $this->api->req->s['uDetails']['group_id']
        );
        $uCidrKey = CacheKey::uCidr(
            uID: $this->api->req->s['uDetails']['id']
        );
        foreach ([$cCidrKey, $gCidrKey, $uCidrKey] as $key) {
            if (DbFunctions::$gCacheServer->cacheExists(key: $key)) {
                $this->cidrChecked = true;
                $cidrs = json_decode(
                    json: DbFunctions::$gCacheServer->getCache(
                        key: $key
                    ),
                    associative: true
                );
                $isValidIp = false;
                foreach ($cidrs as $cidr) {
                    if ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                        $isValidIp = true;
                        break;
                    }
                }
                if (!$isValidIp) {
                    throw new \Exception(
                        message: 'IP not supported',
                        code: HttpStatus::$BadRequest
                    );
                }
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
            ((int)getenv(name: 'enableRateLimitAtClientLevel')) === 0
            || empty($this->api->req->s['cDetails']['rateLimitMaxRequests'])
            || empty($this->api->req->s['cDetails']['rateLimitSecondsWindow'])
        ) {
            return;
        }

        $rateLimitClientPrefix = getenv(name: 'rateLimitClientPrefix');
        $rateLimitMaxRequests
            = $this->api->req->s['cDetails']['rateLimitMaxRequests'];
        $rateLimitSecondsWindow
            = $this->api->req->s['cDetails']['rateLimitSecondsWindow'];
        $key = $this->api->req->s['cDetails']['id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitClientPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitSecondsWindow: $rateLimitSecondsWindow,
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
            ((int)getenv(name: 'enableRateLimitAtGroupLevel')) === 0
            || empty($this->api->req->s['gDetails']['rateLimitMaxRequests'])
            || empty($this->api->req->s['gDetails']['rateLimitSecondsWindow'])
        ) {
            return;
        }

        $rateLimitGroupPrefix
            = getenv(name: 'rateLimitGroupPrefix');
        $rateLimitMaxRequests
            = $this->api->req->s['gDetails']['rateLimitMaxRequests'];
        $rateLimitSecondsWindow
            = $this->api->req->s['gDetails']['rateLimitSecondsWindow'];
        $key = $this->api->req->s['cDetails']['id'] . ':' .
            $this->api->req->s['uDetails']['id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitGroupPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitSecondsWindow: $rateLimitSecondsWindow,
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
            ((int)getenv(name: 'enableRateLimitAtUserLevel')) === 0
            || empty($this->api->req->s['uDetails']['rateLimitMaxRequests'])
            || empty($this->api->req->s['uDetails']['rateLimitSecondsWindow'])
        ) {
            return;
        }

        $rateLimitUserPrefix = getenv(name: 'rateLimitUserPrefix');
        $rateLimitMaxRequests
            = $this->api->req->s['gDetails']['rateLimitMaxRequests'];
        $rateLimitSecondsWindow
            = $this->api->req->s['gDetails']['rateLimitSecondsWindow'];
        $key = $this->api->req->s['cDetails']['id'] . ':' .
            $this->api->req->s['uDetails']['id'] . ':' .
            $this->api->req->s['uDetails']['user_id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitUserPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitSecondsWindow: $rateLimitSecondsWindow,
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
        if (((int)getenv(name: 'enableRateLimitAtUsersRequestLevel')) === 0) {
            return;
        }

        $rateLimitUserPrefix = getenv(name: 'rateLimitUsersRequestPrefix');
        $rateLimitMaxRequests = getenv(name: 'rateLimitUsersMaxRequests');
        $rateLimitSecondsWindow = getenv(name: 'rateLimitUsersMaxRequestsWindow');
        $key = $this->api->req->s['cDetails']['id'] . ':' .
            $this->api->req->s['uDetails']['id'];

        $this->rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: $rateLimitUserPrefix,
            rateLimitMaxRequests: $rateLimitMaxRequests,
            rateLimitSecondsWindow: $rateLimitSecondsWindow,
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
        if (((int)getenv(name: 'enableRateLimitAtIpLevel')) === 0) {
            return;
        }

        $rateLimitIPPrefix = getenv(name: 'rateLimitIPPrefix');
        $rateLimitIPMaxRequests = getenv(name: 'rateLimitIPMaxRequests');
        $rateLimitIPSecondsWindow = getenv(name: 'rateLimitIPSecondsWindow');
        $key = $this->api->req->IP;

        $this->checkRateLimit(
            rateLimitPrefix: $rateLimitIPPrefix,
            rateLimitMaxRequests: $rateLimitIPMaxRequests,
            rateLimitSecondsWindow: $rateLimitIPSecondsWindow,
            key: $key
        );
    }
}
