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

use Microservices\App\Common;
use Microservices\App\CacheKey;
use Microservices\App\DbFunctions;
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
     * Load User Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadUserDetails(): void
    {
        if (isset($this->api->req->s['uDetails'])) {
             return;
        }

        if (
            isset($_SESSION)
            && isset($_SESSION['id'])
        ) {
            $this->api->req->s['uDetails'] = $_SESSION;
            $this->api->req->s['token'] = 'sessions';
        } elseif (
            ($this->api->req->HTTP_AUTHORIZATION !== null)
            && preg_match(
                pattern: '/Bearer\s(\S+)/',
                subject: $this->api->req->HTTP_AUTHORIZATION,
                matches: $matches
            )
        ) {
            $this->api->req->s['token'] = $matches[1];
            $tokenKey = CacheKey::token(
                token: $this->api->req->s['token']
            );
            if (
                !DbFunctions::$gCacheServer->cacheExists(
                    key: $tokenKey
                )
            ) {
                throw new \Exception(
                    message: 'Token expired',
                    code: HttpStatus::$BadRequest
                );
            }
            $this->api->req->s['uDetails'] = json_decode(
                json: DbFunctions::$gCacheServer->getCache(
                    key: $tokenKey
                ),
                associative: true
            );
            $uniqueHttpRequestHash = $this->api->http['hash'];
            if ($this->api->req->s['uDetails']['uniqueHttpRequestHash'] !== $uniqueHttpRequestHash) {
                throw new \Exception(
                    message: 'Token not supported from this Browser/Device',
                    code: HttpStatus::$PreconditionFailed
                );
            }
        }
        if (empty($this->api->req->s['token'])) {
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
        if (isset($this->api->req->s['gDetails'])) {
             return;
        }

        // Load gDetails
        if (
            empty($this->api->req->s['uDetails']['id'])
            || empty($this->api->req->s['uDetails']['id'])
        ) {
            throw new \Exception(
                message: 'Invalid session',
                code: HttpStatus::$InternalServerError
            );
        }

        $gKey = CacheKey::group(
            gID: $this->api->req->s['uDetails']['group_id']
        );
        if (!DbFunctions::$gCacheServer->cacheExists(key: $gKey)) {
            throw new \Exception(
                message: "Cache '{$gKey}' missing",
                code: HttpStatus::$InternalServerError
            );
        }

        $this->api->req->s['gDetails'] = json_decode(
            json: DbFunctions::$gCacheServer->getCache(
                key: $gKey
            ),
            associative: true
        );
    }
}
