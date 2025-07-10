<?php
/**
 * Middleware
 * php version 8.3
 *
 * @category  Middleware
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App\Middleware;

use Microservices\App\CacheKey;
use Microservices\App\HttpRequest;
use Microservices\App\HttpStatus;

/**
 * Class handling details for Auth middleware
 * php version 8.3
 *
 * @category  Auth_Middleware
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Auth
{
    /**
     * HTTP Request Object
     *
     * @var null|HttpRequest
     */
    private $_req = null;

    /**
     * Constructor
     *
     * @param HttpRequest $req HTTP Request Object
     */
    public function __construct(&$req)
    {
        $this->_req = &$req;
    }

    /**
     * Load User Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadUserDetails(): void
    {
        if (!is_null(value: $this->_req->userDetails)) {
             return;
        }

        if (!is_null(value: $this->_req->HTTP_AUTHORIZATION)
            && preg_match(
                pattern: '/Bearer\s(\S+)/',
                subject: $this->_req->HTTP_AUTHORIZATION,
                matches: $matches
            )
        ) {
            $this->_req->sess['token'] = $matches[1];
            $this->_req->tokenKey = CacheKey::token(
                token: $this->_req->sess['token']
            );
            if (!$this->_req->cache->cacheExists(key: $this->_req->tokenKey)) {
                throw new \Exception(
                    message: 'Token expired',
                    code: HttpStatus::$BadRequest
                );
            }
            $this->_req->userDetails = json_decode(
                json: $this->_req->cache->getCache(
                    key: $this->_req->tokenKey
                ),
                associative: true
            );
            $this->_req->groupId = $this->_req->userDetails['group_id'];
            $this->_req->userId = $this->_req->userDetails['user_id'];

            $this->_req->sess['userDetails'] = &$this->_req->userDetails;
        }
        if (empty($this->_req->sess['token'])) {
            throw new \Exception(
                message: 'Token missing',
                code: HttpStatus::$BadRequest
            );
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
        if (!is_null(value: $this->_req->groupDetails)) {
             return;
        }

        // Load groupDetails
        if (empty($this->_req->userDetails['user_id'])
            || empty($this->_req->userDetails['group_id'])
        ) {
            throw new \Exception(
                message: 'Invalid sess',
                code: HttpStatus::$InternalServerError
            );
        }

        $this->_req->groupKey = CacheKey::group(
            groupId: $this->_req->userDetails['group_id']
        );
        if (!$this->_req->cache->cacheExists(key: $this->_req->groupKey)) {
            throw new \Exception(
                message: "Cache '{$this->_req->groupKey}' missing",
                code: HttpStatus::$InternalServerError
            );
        }

        $this->_req->groupDetails = json_decode(
            json: $this->_req->cache->getCache(
                key: $this->_req->groupKey
            ),
            associative: true
        );

        $this->_req->sess['groupDetails'] = &$this->_req->groupDetails;
    }

    /**
     * Validate request IP
     *
     * @return void
     * @throws \Exception
     */
    public function checkRemoteIp()
    {
        $groupId = $this->_req->userDetails['group_id'];

        $this->_req->cidrKey = CacheKey::cidr(
            groupId: $this->_req->userDetails['group_id']
        );
        if ($this->_req->cache->cacheExists(key: $this->_req->cidrKey)) {
            $this->_req->cidrChecked = true;
            $cidrs = json_decode(
                json: $this->_req->cache->getCache(
                    key: $this->_req->cidrKey
                ),
                associative: true
            );
            $ipNumber = ip2long(ip: $this->_req->REMOTE_ADDR);
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
