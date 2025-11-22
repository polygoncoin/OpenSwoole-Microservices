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
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Load User Details
     *
     * @return void
     * @throws \Exception
     */
    public function loadUserDetails(): void
    {
        if (isset(Common::$req->s['uDetails'])) {
             return;
        }

        if (
            isset($_SESSION)
            && isset($_SESSION['id'])
        ) {
            Common::$req->s['uDetails'] = $_SESSION;
            Common::$req->s['token'] = 'sessions';
        } elseif (
            (Common::$req->HTTP_AUTHORIZATION !== null)
            && preg_match(
                pattern: '/Bearer\s(\S+)/',
                subject: Common::$req->HTTP_AUTHORIZATION,
                matches: $matches
            )
        ) {
            Common::$req->s['token'] = $matches[1];
            $tokenKey = CacheKey::token(
                token: Common::$req->s['token']
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
            Common::$req->s['uDetails'] = json_decode(
                json: DbFunctions::$gCacheServer->getCache(
                    key: $tokenKey
                ),
                associative: true
            );
        }
        if (empty(Common::$req->s['token'])) {
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
        if (isset(Common::$req->s['gDetails'])) {
             return;
        }

        // Load gDetails
        if (
            empty(Common::$req->s['uDetails']['id'])
            || empty(Common::$req->s['uDetails']['id'])
        ) {
            throw new \Exception(
                message: 'Invalid session',
                code: HttpStatus::$InternalServerError
            );
        }

        $gKey = CacheKey::group(
            gID: Common::$req->s['uDetails']['group_id']
        );
        if (!DbFunctions::$gCacheServer->cacheExists(key: $gKey)) {
            throw new \Exception(
                message: "Cache '{$gKey}' missing",
                code: HttpStatus::$InternalServerError
            );
        }

        Common::$req->s['gDetails'] = json_decode(
            json: DbFunctions::$gCacheServer->getCache(
                key: $gKey
            ),
            associative: true
        );
    }
}
