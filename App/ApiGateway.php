<?php
namespace Microservices\App;

use Microservices\App\CacheKey;
use Microservices\App\DbFunctions;
use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;

/**
 * Microservices Class
 *
 * Class to start Services
 *
 * @category   Api Gateway
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class ApiGateway extends DbFunctions
{
    /**
     * Details var from $httpRequestDetails
     */
    public $HOST = null;
    public $REQUEST_METHOD = null;
    public $HTTP_AUTHORIZATION = null;
    public $REMOTE_ADDR = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    private $httpRequestDetails = null;

    /**
     * Caching Object
     *
     * @var null|Cache
     */
    public $cache = null;

    /**
     * Bearer token
     */
    private $token = null;

    /**
     * Cache Keys
     */
    private $tokenKey = null;
    private $clientKey = null;
    private $groupKey = null;
    private $cidrKey = null;
    private $cidrChecked = false;

    /**
     * Client Info
     *
     * @var null|array
     */
    private $clientDetails = null;

    /**
     * Group Info
     *
     * @var null|array
     */
    private $groupDetails = null;

    /**
     * User Info
     *
     * @var null|array
     */
    private $userDetails = null;

    /**
     * Rate Limiter
     *
     * @var null|RateLimiter
     */
    private $rateLimiter = null;

        /**
     * Constructor
     *
     * @param array $httpRequestDetails
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->httpRequestDetails = &$httpRequestDetails;

        $this->HOST = $this->httpRequestDetails['server']['host'];
        $this->REQUEST_METHOD = $this->httpRequestDetails['server']['request_method'];
        if (isset($this->httpRequestDetails['header']['authorization'])) {
            $this->HTTP_AUTHORIZATION = $this->httpRequestDetails['header']['authorization'];
        }
        $this->REMOTE_ADDR = $this->httpRequestDetails['server']['remote_addr'];

        $this->cache = $this->setCache(
            getenv('cacheType'),
            getenv('cacheHostname'),
            getenv('cachePort'),
            getenv('cacheUsername'),
            getenv('cachePassword'),
            getenv('cacheDatabase')
        );
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->checkHost();
        $this->checkToken();
        $this->checkRemoteIp();
        $this->checkRateLimits();
    }

    /**
     * Check Host request
     *
     * @return void
     * @throws \Exception
     */
    public function checkHost()
    {
        $this->clientKey = CacheKey::Client($this->HOST);
        if (!$this->cache->cacheExists($this->clientKey)) {
            throw new \Exception("Invalid Host '{$this->HOST}'", HttpStatus::$InternalServerError);
        }
        $this->clientDetails = json_decode($this->cache->getCache($this->clientKey), true);
    }

    /**
     * Check HTTP_AUTHORIZATION token
     *
     * @return void
     * @throws \Exception
     */
    public function checkToken()
    {
        if (!is_null($this->HTTP_AUTHORIZATION) && preg_match('/Bearer\s(\S+)/', $this->HTTP_AUTHORIZATION, $matches)) {
            $this->token = $matches[1];

            $this->tokenKey = CacheKey::Token($this->token);
            if (!$this->cache->cacheExists($this->tokenKey)) {
                throw new \Exception('Token expired', HttpStatus::$BadRequest);
            }
            $this->userDetails = json_decode($this->cache->getCache($this->tokenKey), true);

            // Load groupDetails
            if (empty($this->userDetails['user_id']) || empty($this->userDetails['group_id'])) {
                throw new \Exception('Invalid session', HttpStatus::$InternalServerError);
            }

            $this->groupKey = CacheKey::Group($this->userDetails['group_id']);
            if (!$this->cache->cacheExists($this->groupKey)) {
                throw new \Exception("Cache '{$this->groupKey}' missing", HttpStatus::$InternalServerError);
            }
            $this->groupDetails = json_decode($this->cache->getCache($this->groupKey), true);
        }

        if (empty($this->token)) {
            throw new \Exception('Token missing', HttpStatus::$BadRequest);
        }
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
                $RateLimiterMaxRequests = $this->clientDetails['rateLimiterMaxRequests'],
                $RateLimiterSecondsWindow = $this->clientDetails['rateLimiterSecondsWindow'],
                $RateLimiterGroupPrefix = getenv('RateLimiterClientPrefix'),
                $key = $this->clientDetails['client_id']
            );
        }

        // Group Rate Limiting
        if (
            !empty($this->groupDetails['rateLimiterMaxRequests'])
            && !empty($this->groupDetails['rateLimiterSecondsWindow'])
        ) {
            $rateLimitChecked = $this-checkRateLimit(
                $RateLimiterMaxRequests = $this->groupDetails['rateLimiterMaxRequests'],
                $RateLimiterSecondsWindow = $this->groupDetails['rateLimiterSecondsWindow'],
                $RateLimiterGroupPrefix = getenv('RateLimiterGroupPrefix'),
                $key = $this->clientDetails['client_id'] . ':' . $this->userDetails['group_id']
            );
        }

        // User Rate Limiting
        if (
            !empty($this->userDetails['rateLimiterMaxRequests'])
            && !empty($this->userDetails['rateLimiterSecondsWindow'])
        ) {
            $rateLimitChecked = $this->checkRateLimit(
                $RateLimiterMaxRequests = $this->groupDetails['rateLimiterMaxRequests'],
                $RateLimiterSecondsWindow = $this->groupDetails['rateLimiterSecondsWindow'],
                $RateLimiterUserPrefix = getenv('RateLimiterUserPrefix'),
                $key = $this->clientDetails['client_id'] . ':' . $this->userDetails['group_id'] . ':' . $this->userDetails['user_id']
            );
        }

        // Rate limit open traffic (not limited by allowed IPs/CIDR and allowed Rate Limits to users)
        if ($this->cidrChecked === false && $rateLimitChecked === false) {
            $this->checkRateLimit(
                $RateLimiterIPMaxRequests = getenv('RateLimiterIPMaxRequests'),
                $RateLimiterIPSecondsWindow = getenv('RateLimiterIPSecondsWindow'),
                $RateLimiterIPPrefix = getenv('RateLimiterIPPrefix'),
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
    private function checkRateLimit(
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
