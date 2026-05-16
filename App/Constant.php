<?php

/**
 * Constant
 * php version 8.3
 *
 * @category  Constant
 * @package   Openswoole_Microservices
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
 * @package   Openswoole_Microservices
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

	public static $PRODUCTION = 1;
	public static $DEVELOPMENT = 0;

	public static $TOKEN_EXPIRY_TIME = 25 * 24 * 3600;
	public static $REQUIRED = true;

	public static $ROOT = null;
	public static $WWW = null;
	public static $FILES_DIR = null;
	public static $DROP_BOX_DIR = null;

	public static $OUTPUT_FORMAT_DIR = null;
	public static $OUTPUT_FORMAT_PRIVATE_DIR = null;
	public static $OUTPUT_FORMAT_PUBLIC_DIR = null;

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

		self::$FILES_DIR = self::$WWW . DIRECTORY_SEPARATOR . 'File';
		self::$DROP_BOX_DIR = self::$FILES_DIR . DIRECTORY_SEPARATOR . 'Dropbox';

		self::$OUTPUT_FORMAT_DIR = self::$FILES_DIR . DIRECTORY_SEPARATOR . 'ServingFile';
		self::$OUTPUT_FORMAT_PRIVATE_DIR = self::$OUTPUT_FORMAT_DIR . DIRECTORY_SEPARATOR . 'Private';
		self::$OUTPUT_FORMAT_PUBLIC_DIR = self::$OUTPUT_FORMAT_DIR . DIRECTORY_SEPARATOR . 'Public';

		self::$HTML_PRIVATE_DIR = self::$OUTPUT_FORMAT_PRIVATE_DIR . DIRECTORY_SEPARATOR . 'HTML';
		self::$PHP_PRIVATE_DIR = self::$OUTPUT_FORMAT_PRIVATE_DIR . DIRECTORY_SEPARATOR . 'PHP';
		self::$XSLT_PRIVATE_DIR = self::$OUTPUT_FORMAT_PRIVATE_DIR . DIRECTORY_SEPARATOR . 'XSLT';

		self::$HTML_PUBLIC_DIR = self::$OUTPUT_FORMAT_PUBLIC_DIR . DIRECTORY_SEPARATOR . 'HTML';
		self::$PHP_PUBLIC_DIR = self::$OUTPUT_FORMAT_PUBLIC_DIR . DIRECTORY_SEPARATOR . 'PHP';
		self::$XSLT_PUBLIC_DIR = self::$OUTPUT_FORMAT_PUBLIC_DIR . DIRECTORY_SEPARATOR . 'XSLT';

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
