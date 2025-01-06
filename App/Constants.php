<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

/**
 * Constants
 *
 * Contains all constants related to Microservices
 *
 * @category   Constants
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Constants
{
    static public $GET       = 'GET';
    static public $POST      = 'POST';
    static public $PUT       = 'PUT';
    static public $PATCH     = 'PATCH';
    static public $DELETE    = 'DELETE';

    static public $PRODUCTION = 1;
    static public $DEVELOPMENT = 0;

    static public $TOKEN_EXPIRY_TIME = 3600;
    static public $REQUIRED = true;

    static public $DOC_ROOT = null;
    static public $ROUTE_URL_PARAM = 'r';

    static private $initialized = null;

    static public function init()
    {
        if (!is_null(self::$initialized)) return;

        self::$DOC_ROOT = dirname(__DIR__ . '../');
        self::$initialized = true;
    }
}
