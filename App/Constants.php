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
    public static $LOGS_DIR = null;
    public static $FILES_DIR = null;
    public static $DROP_BOX_DIR = null;
    public static $HTML_DIR = null;
    public static $XSLT_DIR = null;

    public static $AUTH_ROUTES_DIR = null;
    public static $OPEN_ROUTES_DIR = null;
    public static $AUTH_QUERIES_DIR = null;
    public static $OPEN_QUERIES_DIR = null;

    public static $WEB_COOKIES_DIR = null;

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

        self::$LOGS_DIR = self::$PUBLIC_HTML . DIRECTORY_SEPARATOR . 'Logs';

        self::$FILES_DIR = self::$PUBLIC_HTML . DIRECTORY_SEPARATOR . 'Files';
        self::$DROP_BOX_DIR = self::$FILES_DIR . DIRECTORY_SEPARATOR . 'Dropbox';
        self::$HTML_DIR = self::$FILES_DIR . DIRECTORY_SEPARATOR . 'HTML';
        self::$XSLT_DIR = self::$FILES_DIR . DIRECTORY_SEPARATOR . 'XSLT';

        self::$AUTH_ROUTES_DIR = self::$PUBLIC_HTML . DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Routes' .
            DIRECTORY_SEPARATOR . 'Auth';

        self::$OPEN_ROUTES_DIR = self::$PUBLIC_HTML . DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Routes' .
            DIRECTORY_SEPARATOR . 'Open';

        self::$AUTH_QUERIES_DIR = self::$PUBLIC_HTML . DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Queries' .
            DIRECTORY_SEPARATOR . 'Auth';

        self::$OPEN_QUERIES_DIR = self::$PUBLIC_HTML . DIRECTORY_SEPARATOR . 'Config' .
            DIRECTORY_SEPARATOR . 'Queries' .
            DIRECTORY_SEPARATOR . 'Open';

        self::$WEB_COOKIES_DIR = self::$DOC_ROOT . DIRECTORY_SEPARATOR . 'WebCookies';
        if (!is_dir(filename: self::$WEB_COOKIES_DIR)) {
            mkdir(directory: self::$WEB_COOKIES_DIR, permissions: 0755, recursive: true);
        }

        self::$initialized = true;
    }
}
