<?php
/**
 * Middleware
 * php version 8.3
 *
 * @category  Middleware
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
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
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
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
        if (isset($this->_req->s['uDetails'])) {
             return;
        }

        if (($this->_req->HTTP_AUTHORIZATION !== null)
            && preg_match(
                pattern: '/Bearer\s(\S+)/',
                subject: $this->_req->HTTP_AUTHORIZATION,
                matches: $matches
            )
        ) {
            $this->_req->s['token'] = $matches[1];
            $tokenKey = CacheKey::token(
                token: $this->_req->s['token']
            );
            if (!$this->_req->cache->cacheExists(
                key: $tokenKey
            )
            ) {
                throw new \Exception(
                    message: 'Token expired',
                    code: HttpStatus::$BadRequest
                );
            }
            $this->_req->s['uDetails'] = json_decode(
                json: $this->_req->cache->getCache(
                    key: $tokenKey
                ),
                associative: true
            );
        }
        if (empty($this->_req->s['token'])) {
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
    public function loadGroupDetails(): void
    {
        if (isset($this->_req->s['gDetails'])) {
             return;
        }

        // Load gDetails
        if (empty($this->_req->s['uDetails']['id'])
            || empty($this->_req->s['uDetails']['id'])
        ) {
            throw new \Exception(
                message: 'Invalid session',
                code: HttpStatus::$InternalServerError
            );
        }

        $gKey = CacheKey::group(
            gID: $this->_req->s['uDetails']['group_id']
        );
        if (!$this->_req->cache->cacheExists(key: $gKey)) {
            throw new \Exception(
                message: "Cache '{$gKey}' missing",
                code: HttpStatus::$InternalServerError
            );
        }

        $this->_req->s['gDetails'] = json_decode(
            json: $this->_req->cache->getCache(
                key: $gKey
            ),
            associative: true
        );
    }
}
