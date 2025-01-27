<?php
namespace Microservices\App;

/**
 * Http Status
 *
 * Contains all constants related to Microservices
 *
 * @category   Http Status
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class HttpStatus
{
    static public $Ok                   = 200;
    static public $Created              = 201;
    static public $NoContent            = 204;

    static public $MovedPermanently     = 301;
    static public $Found                = 302;
    static public $TemporaryRedirect    = 307;
    static public $PermanentRedirect    = 308;

    static public $BadRequest           = 400;
    static public $Unauthorized         = 401;
    static public $Forbidden            = 403;
    static public $NotFound             = 404;
    static public $MethodNotAllowed     = 405;
    static public $RequestTimeout       = 408;
    static public $Conflict             = 409;
    static public $Gone                 = 410;
    static public $PreconditionFailed   = 412;
    static public $UnsupportedMediaType = 415;
    static public $UnprocessableEntity  = 422;
    static public $TooEarly             = 425;
    static public $TooManyRequests      = 429;

    static public $InternalServerError  = 500;
    static public $BadGateway           = 502;
    static public $ServiceUnavailable   = 503;
    static public $GatewayTimeout       = 504;
}
