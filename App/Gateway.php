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
     * Constructor
     *
     * @param HttpRequest $req HTTP Request object
     */
    public function __construct()
    {
    }

    /**
     * Initialize Gateway
     *
     * @return void
     */
    public function initGateway(): void
    {
        Common::$req->loadClientDetails();

        if (!Common::$req->open) {
            Common::$req->auth->loadUserDetails();
            $this->checkRemoteIp();
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

        if (!Common::$req->open) {
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
                    message: $result['resetAt'] - Common::$timestamp,
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
     * Validate request IP
     *
     * @return void
     * @throws \Exception
     */
    public function checkRemoteIp(): void
    {
        $ipNumber = ip2long(ip: Common::$req->IP);
    
        $cCidrKey = CacheKey::cCidr(
            cID: Common::$req->s['cDetails']['id']
        );
        $gCidrKey = CacheKey::gCidr(
            gID: Common::$req->s['uDetails']['group_id']
        );
        $uCidrKey = CacheKey::uCidr(
            uID: Common::$req->s['uDetails']['id']
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
            || empty(Common::$req->s['cDetails']['rateLimitMaxRequests'])
            || empty(Common::$req->s['cDetails']['rateLimitSecondsWindow'])
        ) {
            return;
        }

        $rateLimitClientPrefix = getenv(name: 'rateLimitClientPrefix');
        $rateLimitMaxRequests
            = Common::$req->s['cDetails']['rateLimitMaxRequests'];
        $rateLimitSecondsWindow
            = Common::$req->s['cDetails']['rateLimitSecondsWindow'];
        $key = Common::$req->s['cDetails']['id'];

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
            || empty(Common::$req->s['gDetails']['rateLimitMaxRequests'])
            || empty(Common::$req->s['gDetails']['rateLimitSecondsWindow'])
        ) {
            return;
        }

        $rateLimitGroupPrefix
            = getenv(name: 'rateLimitGroupPrefix');
        $rateLimitMaxRequests
            = Common::$req->s['gDetails']['rateLimitMaxRequests'];
        $rateLimitSecondsWindow
            = Common::$req->s['gDetails']['rateLimitSecondsWindow'];
        $key = Common::$req->s['cDetails']['id'] . ':' .
            Common::$req->s['uDetails']['id'];

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
            || empty(Common::$req->s['uDetails']['rateLimitMaxRequests'])
            || empty(Common::$req->s['uDetails']['rateLimitSecondsWindow'])
        ) {
            return;
        }

        $rateLimitUserPrefix = getenv(name: 'rateLimitUserPrefix');
        $rateLimitMaxRequests
            = Common::$req->s['gDetails']['rateLimitMaxRequests'];
        $rateLimitSecondsWindow
            = Common::$req->s['gDetails']['rateLimitSecondsWindow'];
        $key = Common::$req->s['cDetails']['id'] . ':' .
            Common::$req->s['uDetails']['id'] . ':' .
            Common::$req->s['uDetails']['user_id'];

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
        $key = Common::$req->s['cDetails']['id'] . ':' .
            Common::$req->s['uDetails']['id'];

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
        $key = Common::$req->IP;

        $this->checkRateLimit(
            rateLimitPrefix: $rateLimitIPPrefix,
            rateLimitMaxRequests: $rateLimitIPMaxRequests,
            rateLimitSecondsWindow: $rateLimitIPSecondsWindow,
            key: $key
        );
    }
}
