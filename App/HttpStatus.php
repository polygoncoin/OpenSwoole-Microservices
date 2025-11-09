<?php

/**
 * HTTP Status
 * php version 8.3
 *
 * @category  HTTP_Status
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/**
 * HTTP Status
 * php version 8.3
 *
 * @category  HTTP_Status
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpStatus
{
    public static $Ok                   = 200;
    public static $Created              = 201;
    public static $NoContent            = 204;

    public static $MovedPermanently     = 301;
    public static $Found                = 302;
    public static $NotModified          = 304;
    public static $TemporaryRedirect    = 307;
    public static $PermanentRedirect    = 308;

    public static $BadRequest           = 400;
    public static $Unauthorized         = 401;
    public static $Forbidden            = 403;
    public static $NotFound             = 404;
    public static $MethodNotAllowed     = 405;
    public static $RequestTimeout       = 408;
    public static $Conflict             = 409;
    public static $Gone                 = 410;
    public static $PreconditionFailed   = 412;
    public static $UnsupportedMediaType = 415;
    public static $UnprocessableEntity  = 422;
    public static $TooEarly             = 425;
    public static $TooManyRequests      = 429;

    public static $InternalServerError  = 500;
    public static $BadGateway           = 502;
    public static $ServiceUnavailable   = 503;
    public static $GatewayTimeout       = 504;
}
