<?php
namespace Microservices\App\Middleware;

use Microservices\App\CacheKey;
use Microservices\App\HttpRequest;
use Microservices\App\HttpStatus;

/*
 * Class handling details for Auth middleware
 *
 * @category   Auth Middleware
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Auth
{
    /**
     * @var null|HttpRequest
     */
    private $httpRequest = null;

     /**
     * Constructor
     *
     * @param HttpRequest $httpRequest
     */
    public function __construct(&$httpRequest)
    {
        $this->httpRequest = &$httpRequest;
    }

    /**
     * Load User Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadUserDetails()
    {
        if (!is_null($this->httpRequest->userDetails)) return;

        if (
            !is_null($this->httpRequest->HTTP_AUTHORIZATION)
            && preg_match('/Bearer\s(\S+)/', $this->httpRequest->HTTP_AUTHORIZATION, $matches)
        ) {
            $this->httpRequest->session['token'] = $matches[1];
            $this->httpRequest->tokenKey = CacheKey::Token($this->httpRequest->session['token']);
            if (!$this->httpRequest->cache->cacheExists($this->httpRequest->tokenKey)) {
                throw new \Exception('Token expired', HttpStatus::$BadRequest);
            }
            $this->httpRequest->userDetails = json_decode($this->httpRequest->cache->getCache($this->httpRequest->tokenKey), true);
            $this->httpRequest->groupId = $this->httpRequest->userDetails['group_id'];
            $this->httpRequest->userId = $this->httpRequest->userDetails['user_id'];

            $this->httpRequest->session['userDetails'] = &$this->httpRequest->userDetails;
        }
        if (empty($this->httpRequest->session['token'])) {
            throw new \Exception('Token missing', HttpStatus::$BadRequest);
        }
    }

    /**
     * Load User Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadGroupDetails()
    {
        if (!is_null($this->httpRequest->groupDetails)) return;

        // Load groupDetails
        if (empty($this->httpRequest->userDetails['user_id']) || empty($this->httpRequest->userDetails['group_id'])) {
            throw new \Exception('Invalid session', HttpStatus::$InternalServerError);
        }

        $this->httpRequest->groupKey = CacheKey::Group($this->httpRequest->userDetails['group_id']);
        if (!$this->httpRequest->cache->cacheExists($this->httpRequest->groupKey)) {
            throw new \Exception("Cache '{$this->httpRequest->groupKey}' missing", HttpStatus::$InternalServerError);
        }

        $this->httpRequest->groupDetails = json_decode($this->httpRequest->cache->getCache($this->httpRequest->groupKey), true);

        $this->httpRequest->session['groupDetails'] = &$this->httpRequest->groupDetails;
    }

    /**
     * Validate request IP
     *
     * @return void
     * @throws \Exception
     */
    public function checkRemoteIp()
    {
        $groupId = $this->httpRequest->userDetails['group_id'];

        $this->httpRequest->cidrKey = CacheKey::CIDR($this->httpRequest->userDetails['group_id']);
        if ($this->httpRequest->cache->cacheExists($this->httpRequest->cidrKey)) {
            $this->httpRequest->cidrChecked = true;
            $cidrs = json_decode($this->httpRequest->cache->getCache($this->httpRequest->cidrKey), true);
            $ipNumber = ip2long($this->httpRequest->REMOTE_ADDR);
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
}
