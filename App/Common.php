<?php

/**
 * Common
 * php version 8.3
 *
 * @category  Common
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\HttpRequest;
use Microservices\App\HttpResponse;

/**
 * Common Class
 * php version 8.3
 *
 * @category  Common
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Common
{
    /**
     * Unix timestamp
     *
     * @var null|int
     */
    public static $timestamp = null;

    /**
     * Microservices HTTP Request
     *
     * @var null|HttpRequest
     */
    public static $req = null;

    /**
     * Microservices HTTP Response
     *
     * @var null|HttpResponse
     */
    public static $res = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public static $http = null;

    /**
     * Initialize
     *
     * @param array $http HTTP request details
     *
     * @return void
     */
    public static function init(&$http): void
    {
        self::$timestamp = time();
        self::$http = &$http;
        self::$req = new HttpRequest(http: self::$http);
        self::$res = new HttpResponse(http: self::$http);
    }

    /**
     * Initialize Request
     *
     * @return bool
     */
    public static function initRequest(): void
    {
        self::$req->init();
    }

    /**
     * Initialize Response
     *
     * @return bool
     */
    public static function initResponse(): void
    {
        self::$res->init();
    }
}
