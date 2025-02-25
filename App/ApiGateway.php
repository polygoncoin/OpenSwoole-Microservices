<?php
namespace Microservices\App;

use Microservices\App\CacheKey;
use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;

/**
 * Microservices Class
 *
 * Class to start Services.
 *
 * @category   Api Gateway
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class ApiGateway
{
    /**
     * Details var from $httpRequestDetails.
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
     * Redis Object
     *
     * @var null|\Redis
     */
    private $redis = null;

    /**
     * Bearer token
     */
    private $token = null;

    /**
     * Redis Keys
     */
    private $tokenKey = null;
    private $clientKey = null;
    private $groupKey = null;
    private $cidr_key = null;

    /**
     * User Info
     *
     * @var null|array
     */
    private $userDetails = null;

    /**
     * Group Info
     *
     * @var null|array
     */
    private $groupDetails = null;

    /**
     * Constructor
     *
     * @param array $httpRequestDetails
     */
    public function __construct($httpRequestDetails)
    {
        $this->httpRequestDetails = $httpRequestDetails;

        $this->HOST = $this->httpRequestDetails['server']['host'];
        $this->REQUEST_METHOD = $this->httpRequestDetails['server']['request_method'];
        if (isset($this->httpRequestDetails['header']['authorization'])) {
            $this->HTTP_AUTHORIZATION = $this->httpRequestDetails['header']['authorization'];
        }
        $this->REMOTE_ADDR = $this->httpRequestDetails['server']['remote_addr'];

        if (!extension_loaded('redis')) {
            throw new \Exception("Unable to find Redis extension", HttpStatus::$InternalServerError);
        }
        $this->redis = new \Redis();
        $this->redis->connect(getenv('RateLimiterHost'), (int)getenv('RateLimiterHostPort'));

        $this->checkRateLimit(
            $RateLimiterIPMaxRequests = getenv('RateLimiterIPMaxRequests'),
            $RateLimiterIPSecondsWindow = getenv('RateLimiterIPSecondsWindow'),
            $RateLimiterIPPrefix = getenv('RateLimiterIPPrefix'),
            $key = $this->REMOTE_ADDR
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
        if (!$this->redis->exists($this->clientKey)) {
            throw new \Exception("Invalid Host '{$this->HOST}'", HttpStatus::$InternalServerError);
        }
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
            if (!$this->redis->exists($this->tokenKey)) {
                throw new \Exception('Token expired', HttpStatus::$BadRequest);
            }
            $this->userDetails = json_decode($this->redis->get($this->tokenKey), true);

            // Check IP
            $this->checkRemoteIp();

            // Load groupDetails
            if (empty($this->userDetails['user_id']) || empty($this->userDetails['group_id'])) {
                throw new \Exception('Invalid session', HttpStatus::$InternalServerError);
            }

            $this->groupKey = CacheKey::Group($this->userDetails['group_id']);
            if (!$this->redis->exists($this->groupKey)) {
                throw new \Exception("Cache '{$this->groupKey}' missing", HttpStatus::$InternalServerError);
            }

            $this->groupDetails = json_decode($this->redis->get($this->groupKey), true);

            // Check Rate Limit
            if (
                !empty($this->groupDetails['rateLimiterMaxRequests'])
                && !empty($this->groupDetails['rateLimiterSecondsWindow'])
            ) {
                $this-checkRateLimit(
                    $RateLimiterMaxRequests = $this->groupDetails['rateLimiterMaxRequests'],
                    $RateLimiterSecondsWindow = $this->groupDetails['rateLimiterSecondsWindow'],
                    $RateLimiterGroupPrefix = getenv('RateLimiterGroupPrefix'),
                    $key = $this->userDetails['group_id'] . ':' . $this->userDetails['user_id']
                );
            }

            if (
                !empty($this->userDetails['rateLimiterMaxRequests'])
                && !empty($this->userDetails['rateLimiterSecondsWindow'])
            ) {
                $this->checkRateLimit(
                    $RateLimiterMaxRequests = $this->groupDetails['rateLimiterMaxRequests'],
                    $RateLimiterSecondsWindow = $this->groupDetails['rateLimiterSecondsWindow'],
                    $RateLimiterUserPrefix = getenv('RateLimiterUserPrefix'),
                    $key = $this->userDetails['group_id'] . ':' . $this->userDetails['user_id']
                );
            }
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

        $this->cidr_key = CacheKey::CIDR($this->userDetails['group_id']);
        if ($this->redis->exists($this->cidr_key)) {
            $cidrs = json_decode($this->redis->get($this->cidr_key), true);
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
     * Check Rate Limit
     *
     * @return void
     */
    private function checkRateLimit(
        $RateLimiterPrefix,
        $RateLimiterMaxRequests,
        $RateLimiterSecondsWindow,
        $key
    ) {
        try {
            $rateLimiter = new RateLimiter(
                $this->redis,
                $RateLimiterPrefix,
                $RateLimiterMaxRequests,
                $RateLimiterSecondsWindow
            );

            $result = $rateLimiter->check($key);

            if ($result['allowed']) {
                // Process the request
                return;
            } else {
                // Return 429 Too Many Requests
                http_response_code(429);
                header('Retry-After: ' . ($result['resetAt'] - time()));
                die(json_encode([
                    'error' => 'Too Many Requests',
                    'retryAfter' => $result['resetAt']
                ]));
            }

        } catch (\Exception $e) {
            // Handle connection errors
            die('Rate limiter error: ' . $e->getMessage());
        }
    }
}
