<?php

/**
 * Constants
 * php version 8.3
 *
 * @category  Constants
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/**
 * Constants
 * php version 8.3
 *
 * @category  Constants
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Constants
{
    public static $GET       = 'GET';
    public static $POST      = 'POST';
    public static $PUT       = 'PUT';
    public static $PATCH     = 'PATCH';
    public static $DELETE    = 'DELETE';

    public static $PRODUCTION = 1;
    public static $DEVELOPMENT = 0;

    public static $TOKEN_EXPIRY_TIME = 3600;
    public static $REQUIRED = true;

    public static $DOC_ROOT = null;
    public static $PUBLIC_HTML = null;
    public static $ROUTE_URL_PARAM = 'r';

    private static $initialized = false;

    /**
     * Initialize
     *
     * @return void
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$DOC_ROOT = dirname(path: __DIR__ . '..' . DIRECTORY_SEPARATOR);
        self::$PUBLIC_HTML = self::$DOC_ROOT;
        self::$initialized = true;
    }
}
