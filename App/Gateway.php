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
    private $_rateLimiter = null;

    /**
     * Rate Limiter
     *
     * @var null|HttpRequest
     */
    private $_req = null;

    /**
     * Constructor
     *
     * @param HttpRequest $req HTTP Request object
     */
    public function __construct(&$req)
    {
        $this->_req = &$req;
    }

    /**
     * Initialize Gateway
     *
     * @return void
     */
    public function initGateway(): void
    {
        $this->_req->loadClientDetails();

        if (!$this->_req->open) {
            $this->_req->auth->loadUserDetails();
            $this->checkRemoteIp();
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
        if (!empty($this->_req->s['cDetails']['rateLimitMaxRequests'])
            && !empty($this->_req->s['cDetails']['rateLimitSecondsWindow'])
        ) {
            $rateLimitClientPrefix = getenv(name: 'rateLimitClientPrefix');
            $rateLimitMaxRequests = $this->_req->s['cDetails']['rateLimitMaxRequests'];
            $rateLimitSecondsWindow
                = $this->_req->s['cDetails']['rateLimitSecondsWindow'];
            $key = $this->_req->s['cDetails']['id'];

            $rateLimitChecked = $this->checkRateLimit(
                rateLimitPrefix: $rateLimitClientPrefix,
                rateLimitMaxRequests: $rateLimitMaxRequests,
                rateLimitSecondsWindow: $rateLimitSecondsWindow,
                key: $key
            );
        }

        if (!$this->_req->open) {
            // Group Rate Limiting
            if (!empty($this->_req->s['gDetails']['rateLimitMaxRequests'])
                && !empty($this->_req->s['gDetails']['rateLimitSecondsWindow'])
            ) {
                $rateLimitGroupPrefix
                    = getenv(name: 'rateLimitGroupPrefix');
                $rateLimitMaxRequests
                    = $this->_req->s['gDetails']['rateLimitMaxRequests'];
                $rateLimitSecondsWindow
                    = $this->_req->s['gDetails']['rateLimitSecondsWindow'];
                $key = $this->_req->s['cDetails']['id'] . ':' .
                    $this->_req->s['uDetails']['id'];

                $rateLimitChecked = $this->checkRateLimit(
                    rateLimitPrefix: $rateLimitGroupPrefix,
                    rateLimitMaxRequests: $rateLimitMaxRequests,
                    rateLimitSecondsWindow: $rateLimitSecondsWindow,
                    key: $key
                );
            }

            // User Rate Limiting
            if (!empty($this->_req->s['uDetails']['rateLimitMaxRequests'])
                && !empty($this->_req->s['uDetails']['rateLimitSecondsWindow'])
            ) {
                $rateLimitUserPrefix = getenv(name: 'rateLimitUserPrefix');
                $rateLimitMaxRequests
                    = $this->_req->s['gDetails']['rateLimitMaxRequests'];
                $rateLimitSecondsWindow
                    = $this->_req->s['gDetails']['rateLimitSecondsWindow'];
                $key = $this->_req->s['cDetails']['id'] . ':' .
                    $this->_req->s['uDetails']['id'] . ':' .
                    $this->_req->s['uDetails']['user_id'];

                $rateLimitChecked = $this->checkRateLimit(
                    rateLimitPrefix: $rateLimitUserPrefix,
                    rateLimitMaxRequests: $rateLimitMaxRequests,
                    rateLimitSecondsWindow: $rateLimitSecondsWindow,
                    key: $key
                );
            }
        }

        // Rate limit open traffic (not limited by allowed IPs/CIDR and allowed
        // Rate Limits to users)
        if ($this->cidrChecked === false && $rateLimitChecked === false) {
            $rateLimitIPPrefix = getenv(name: 'rateLimitIPPrefix');
            $rateLimitIPMaxRequests = getenv(name: 'rateLimitIPMaxRequests');
            $rateLimitIPSecondsWindow = getenv(name: 'rateLimitIPSecondsWindow');
            $key = $this->_req->IP;

            $this->checkRateLimit(
                rateLimitPrefix: $rateLimitIPPrefix,
                rateLimitMaxRequests: $rateLimitIPMaxRequests,
                rateLimitSecondsWindow: $rateLimitIPSecondsWindow,
                key: $key
            );
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
            $result = $this->_rateLimiter->check(
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
            gID: $this->_req->s['uDetails']['group_id']
        );
        if ($this->_req->cache->cacheExists(key: $cidrKey)) {
            $this->cidrChecked = true;
            $cidrs = json_decode(
                json: $this->_req->cache->getCache(
                    key: $cidrKey
                ),
                associative: true
            );
            $ipNumber = ip2long(ip: $this->_req->IP);
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
