<?php

/**
 * Constant
 * php version 8.3
 *
 * @category  Constant
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/**
 * Constant
 * php version 8.3
 *
 * @category  Constant
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Constant
{
	public static $GET       = 'GET';
	public static $POST      = 'POST';
	public static $PUT       = 'PUT';
	public static $PATCH     = 'PATCH';
	public static $DELETE    = 'DELETE';
	public static $OPTIONS   = 'OPTIONS';

	public static $PRODUCTION = 1;
	public static $DEVELOPMENT = 0;

	public static $TOKEN_EXPIRY_TIME = 25 * 24 * 3600;
	public static $REQUIRED = true;

	public static $ROOT = null;
	public static $WWW = null;
	public static $FILE_DIR = null;

	public static $DROPBOX_DIR = null;
	public static $DROPBOX_PRIVATE_DIR = null;
	public static $DROPBOX_PUBLIC_DIR = null;

	public static $SERVING_FILE_DIR = null;
	public static $SERVING_FILE_PRIVATE_DIR = null;
	public static $SERVING_FILE_PUBLIC_DIR = null;

	public static $HTML_PRIVATE_DIR = null;
	public static $PHP_PRIVATE_DIR = null;
	public static $XSLT_PRIVATE_DIR = null;

	public static $HTML_PUBLIC_DIR = null;
	public static $PHP_PUBLIC_DIR = null;
	public static $XSLT_PUBLIC_DIR = null;

	public static $ROUTES_PRIVATE_DIR = null;
	public static $ROUTES_PUBLIC_DIR = null;

	public static $QUERIES_PRIVATE_DIR = null;
	public static $QUERIES_PUBLIC_DIR = null;

	public static $WEB_COOKIES_DIR = null;
	public static $LOG_DIR = null;

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

		self::$ROOT = dirname(path: __DIR__ . '..' . DIRECTORY_SEPARATOR);
		self::$WWW = self::$ROOT;

		self::$FILE_DIR = self::$WWW . DIRECTORY_SEPARATOR . 'File';

		self::$DROPBOX_DIR = self::$FILE_DIR . DIRECTORY_SEPARATOR . 'Dropbox';
		self::$DROPBOX_PRIVATE_DIR = self::$DROPBOX_DIR . DIRECTORY_SEPARATOR . 'Private';
		self::$DROPBOX_PUBLIC_DIR = self::$DROPBOX_DIR . DIRECTORY_SEPARATOR . 'Public';

		self::$SERVING_FILE_DIR = self::$FILE_DIR . DIRECTORY_SEPARATOR . 'ServingFile';
		self::$SERVING_FILE_PRIVATE_DIR = self::$SERVING_FILE_DIR . DIRECTORY_SEPARATOR . 'Private';
		self::$SERVING_FILE_PUBLIC_DIR = self::$SERVING_FILE_DIR . DIRECTORY_SEPARATOR . 'Public';

		self::$HTML_PRIVATE_DIR = self::$SERVING_FILE_PRIVATE_DIR . DIRECTORY_SEPARATOR . 'HTML';
		self::$PHP_PRIVATE_DIR = self::$SERVING_FILE_PRIVATE_DIR . DIRECTORY_SEPARATOR . 'PHP';
		self::$XSLT_PRIVATE_DIR = self::$SERVING_FILE_PRIVATE_DIR . DIRECTORY_SEPARATOR . 'XSLT';

		self::$HTML_PUBLIC_DIR = self::$SERVING_FILE_PUBLIC_DIR . DIRECTORY_SEPARATOR . 'HTML';
		self::$PHP_PUBLIC_DIR = self::$SERVING_FILE_PUBLIC_DIR . DIRECTORY_SEPARATOR . 'PHP';
		self::$XSLT_PUBLIC_DIR = self::$SERVING_FILE_PUBLIC_DIR . DIRECTORY_SEPARATOR . 'XSLT';

		self::$ROUTES_PRIVATE_DIR = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Route'
			. DIRECTORY_SEPARATOR . 'Private';
		self::$ROUTES_PUBLIC_DIR = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Route'
			. DIRECTORY_SEPARATOR . 'Public';

		self::$QUERIES_PRIVATE_DIR = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Sql'
			. DIRECTORY_SEPARATOR . 'Private';
		self::$QUERIES_PUBLIC_DIR = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Sql'
			. DIRECTORY_SEPARATOR . 'Public';

		self::$WEB_COOKIES_DIR = self::$ROOT . DIRECTORY_SEPARATOR . 'WebCookie';
		if (!is_dir(filename: self::$WEB_COOKIES_DIR)) {
			mkdir(directory: self::$WEB_COOKIES_DIR, permissions: 0755, recursive: true);
		}

		self::$LOG_DIR = self::$ROOT . DIRECTORY_SEPARATOR . 'Log';
		if (!is_dir(filename: self::$LOG_DIR)) {
			mkdir(directory: self::$LOG_DIR, permissions: 0755, recursive: true);
		}

		self::$initialized = true;
	}
}
