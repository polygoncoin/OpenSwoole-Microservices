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
     * Rate Limiter
     *
     * @var null|HttpRequest
     */
    private $req = null;

    /**
     * Constructor
     *
     * @param HttpRequest $req HTTP Request object
     */
    public function __construct(&$req)
    {
        $this->req = &$req;
    }

    /**
     * Initialize Gateway
     *
     * @return void
     */
    public function initGateway(): void
    {
        $this->req->loadClientDetails();

        if (!$this->req->open) {
            $this->req->auth->loadUserDetails();
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

        if (!$this->req->open) {
            // Group Rate Limiting
            $this->rateLimitGroup();

            // User Rate Limiting
            $this->rateLimitUser();
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

    /**
     * Validate request IP
     *
     * @return void
     * @throws \Exception
     */
    public function checkRemoteIp(): void
    {
        $cidrKey = CacheKey::cidr(
            gID: $this->req->s['uDetails']['group_id']
        );
        if ($this->req->cache->cacheExists(key: $cidrKey)) {
            $this->cidrChecked = true;
            $cidrs = json_decode(
                json: $this->req->cache->getCache(
                    key: $cidrKey
                ),
                associative: true
            );
            $ipNumber = ip2long(ip: $this->req->IP);
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

    /**
     * Rate Limit Client Request
     *
     * @return void
     */
    private function rateLimitClient(): void
    {
        if (
            !empty($this->req->s['cDetails']['rateLimitMaxRequests'])
            && !empty($this->req->s['cDetails']['rateLimitSecondsWindow'])
        ) {
            $rateLimitClientPrefix = getenv(name: 'rateLimitClientPrefix');
            $rateLimitMaxRequests
                = $this->req->s['cDetails']['rateLimitMaxRequests'];
            $rateLimitSecondsWindow
                = $this->req->s['cDetails']['rateLimitSecondsWindow'];
            $key = $this->req->s['cDetails']['id'];

            $this->rateLimitChecked = $this->checkRateLimit(
                rateLimitPrefix: $rateLimitClientPrefix,
                rateLimitMaxRequests: $rateLimitMaxRequests,
                rateLimitSecondsWindow: $rateLimitSecondsWindow,
                key: $key
            );
        }
    }

    /**
     * Rate Limit Client Group Request
     *
     * @return void
     */
    private function rateLimitGroup(): void
    {
        if (
            !empty($this->req->s['gDetails']['rateLimitMaxRequests'])
            && !empty($this->req->s['gDetails']['rateLimitSecondsWindow'])
        ) {
            $rateLimitGroupPrefix
                = getenv(name: 'rateLimitGroupPrefix');
            $rateLimitMaxRequests
                = $this->req->s['gDetails']['rateLimitMaxRequests'];
            $rateLimitSecondsWindow
                = $this->req->s['gDetails']['rateLimitSecondsWindow'];
            $key = $this->req->s['cDetails']['id'] . ':' .
                $this->req->s['uDetails']['id'];

            $this->rateLimitChecked = $this->checkRateLimit(
                rateLimitPrefix: $rateLimitGroupPrefix,
                rateLimitMaxRequests: $rateLimitMaxRequests,
                rateLimitSecondsWindow: $rateLimitSecondsWindow,
                key: $key
            );
        }
    }

    /**
     * Rate Limit Client Group User Request
     *
     * @return void
     */
    private function rateLimitUser(): void
    {
        if (
            !empty($this->req->s['uDetails']['rateLimitMaxRequests'])
            && !empty($this->req->s['uDetails']['rateLimitSecondsWindow'])
        ) {
            $rateLimitUserPrefix = getenv(name: 'rateLimitUserPrefix');
            $rateLimitMaxRequests
                = $this->req->s['gDetails']['rateLimitMaxRequests'];
            $rateLimitSecondsWindow
                = $this->req->s['gDetails']['rateLimitSecondsWindow'];
            $key = $this->req->s['cDetails']['id'] . ':' .
                $this->req->s['uDetails']['id'] . ':' .
                $this->req->s['uDetails']['user_id'];

            $this->rateLimitChecked = $this->checkRateLimit(
                rateLimitPrefix: $rateLimitUserPrefix,
                rateLimitMaxRequests: $rateLimitMaxRequests,
                rateLimitSecondsWindow: $rateLimitSecondsWindow,
                key: $key
            );
        }
    }

    /**
     * Rate Limit Request from source IP
     *
     * @return void
     */
    private function rateLimitIp(): void
    {
        $rateLimitIPPrefix = getenv(name: 'rateLimitIPPrefix');
        $rateLimitIPMaxRequests = getenv(name: 'rateLimitIPMaxRequests');
        $rateLimitIPSecondsWindow = getenv(name: 'rateLimitIPSecondsWindow');
        $key = $this->req->IP;

        $this->checkRateLimit(
            rateLimitPrefix: $rateLimitIPPrefix,
            rateLimitMaxRequests: $rateLimitIPMaxRequests,
            rateLimitSecondsWindow: $rateLimitIPSecondsWindow,
            key: $key
        );
    }
}
