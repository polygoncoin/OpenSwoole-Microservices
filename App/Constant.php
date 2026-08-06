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
	// HTTP Request Method
	public static $GET       = 'GET';
	public static $QUERY     = 'QUERY';
	public static $POST      = 'POST';
	public static $PUT       = 'PUT';
	public static $PATCH     = 'PATCH';
	public static $DELETE    = 'DELETE';
	public static $OPTIONS   = 'OPTIONS';

	public static $PRODUCTION = 1;
	public static $DEVELOPMENT = 0;

	public static $NULL = null;

	public static $TRUE = true;
	public static $FALSE = false;

	public static $YES = 'Yes';
	public static $NO = 'No';

	public static $TOKEN_EXPIRY_TIME = 25 * 24 * 3600;
	public static $REQUIRED = true;

	public static $ROOT = null;
	public static $WWW = null;
	public static $FILE_DIRECTORY = null;

	public static $SUPPLEMENT_DIRECTORY = null;
	public static $SUPPLEMENT_NS = null;

	public static $DROPBOX_DIRECTORY = null;
	public static $DROPBOX_PRIVATE_DIRECTORY = null;
	public static $DROPBOX_PUBLIC_DIRECTORY = null;

	public static $SERVING_FILE_DIRECTORY = null;
	public static $SERVING_FILE_PRIVATE_DIRECTORY = null;
	public static $SERVING_FILE_PUBLIC_DIRECTORY = null;

	public static $HTML_PRIVATE_DIRECTORY = null;
	public static $PHP_PRIVATE_DIRECTORY = null;
	public static $XSLT_PRIVATE_DIRECTORY = null;

	public static $HTML_PUBLIC_DIRECTORY = null;
	public static $PHP_PUBLIC_DIRECTORY = null;
	public static $XSLT_PUBLIC_DIRECTORY = null;

	public static $ROUTES_CONFIG_PRIVATE_DIRECTORY = null;
	public static $ROUTES_CONFIG_PUBLIC_DIRECTORY = null;

	public static $SQL_CONFIG_PRIVATE_DIRECTORY = null;
	public static $SQL_CONFIG_PUBLIC_DIRECTORY = null;

	public static $WEB_COOKIES_DIRECTORY = null;
	public static $LOG_DIRECTORY = null;

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

		self::$ROOT = dirname(
			path: __DIR__ . '..' . DIRECTORY_SEPARATOR
		);
		self::$WWW = self::$ROOT;
		self::$FILE_DIRECTORY = self::$WWW . DIRECTORY_SEPARATOR . 'File';

		self::$SUPPLEMENT_DIRECTORY = self::$WWW . DIRECTORY_SEPARATOR . 'Supplement';
		self::$SUPPLEMENT_NS = 'Microservices\\Supplement';

		self::$DROPBOX_DIRECTORY = self::$FILE_DIRECTORY . DIRECTORY_SEPARATOR . 'Dropbox';
		self::$DROPBOX_PRIVATE_DIRECTORY = self::$DROPBOX_DIRECTORY . DIRECTORY_SEPARATOR . 'Private';
		self::$DROPBOX_PUBLIC_DIRECTORY = self::$DROPBOX_DIRECTORY . DIRECTORY_SEPARATOR . 'Public';

		self::$SERVING_FILE_DIRECTORY = self::$FILE_DIRECTORY . DIRECTORY_SEPARATOR . 'ServingFile';
		self::$SERVING_FILE_PRIVATE_DIRECTORY = self::$SERVING_FILE_DIRECTORY . DIRECTORY_SEPARATOR . 'Private';
		self::$SERVING_FILE_PUBLIC_DIRECTORY = self::$SERVING_FILE_DIRECTORY . DIRECTORY_SEPARATOR . 'Public';

		self::$HTML_PRIVATE_DIRECTORY = self::$SERVING_FILE_PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . 'HTML';
		self::$PHP_PRIVATE_DIRECTORY = self::$SERVING_FILE_PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . 'PHP';
		self::$XSLT_PRIVATE_DIRECTORY = self::$SERVING_FILE_PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . 'XSLT';

		self::$HTML_PUBLIC_DIRECTORY = self::$SERVING_FILE_PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . 'HTML';
		self::$PHP_PUBLIC_DIRECTORY = self::$SERVING_FILE_PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . 'PHP';
		self::$XSLT_PUBLIC_DIRECTORY = self::$SERVING_FILE_PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . 'XSLT';

		self::$ROUTES_CONFIG_PRIVATE_DIRECTORY = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Route'
			. DIRECTORY_SEPARATOR . 'Private';
		self::$ROUTES_CONFIG_PUBLIC_DIRECTORY = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Route'
			. DIRECTORY_SEPARATOR . 'Public';

		self::$SQL_CONFIG_PRIVATE_DIRECTORY = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Sql'
			. DIRECTORY_SEPARATOR . 'Private';
		self::$SQL_CONFIG_PUBLIC_DIRECTORY = self::$WWW . DIRECTORY_SEPARATOR . 'Config'
			. DIRECTORY_SEPARATOR . 'Sql'
			. DIRECTORY_SEPARATOR . 'Public';

		self::$WEB_COOKIES_DIRECTORY = self::$ROOT . DIRECTORY_SEPARATOR . 'WebCookie';
		if (
			!is_dir(
				filename: self::$WEB_COOKIES_DIRECTORY
			)
		) {
			mkdir(
				directory: self::$WEB_COOKIES_DIRECTORY,
				permissions: 0755,
				recursive: self::$TRUE
			);
		}

		self::$LOG_DIRECTORY = self::$ROOT . DIRECTORY_SEPARATOR . 'Log';
		if (
			!is_dir(
				filename: self::$LOG_DIRECTORY
			)
		) {
			mkdir(
				directory: self::$LOG_DIRECTORY,
				permissions: 0755,
				recursive: self::$TRUE
			);
		}

		self::$initialized = self::$TRUE;
	}
}
