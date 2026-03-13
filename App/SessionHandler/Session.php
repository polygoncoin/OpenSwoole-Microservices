<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\SessionHandler;

use Microservices\App\Constant;
use Microservices\App\SessionHandler\CustomSessionHandler;
use Microservices\App\SessionHandler\Container\SessionContainerInterface;

/**
 * Custom Session Handler Config
 * php version 7
 *
 * @category  CustomSessionHandler_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Session
{
	/**
	 * Domain Name
	 *
	 * @var null|string
	 */
	public static $sessionDomain = null;

	/**
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION PASS PHRASE
	 *
	 * Value = base64_encode(openssl_random_pseudo_bytes(32))
	 * Example: public static $ENCRYPTION_PASS_PHRASE =
	 * 'H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c=';
	 *
	 * @var null|string
	 */
	public static $ENCRYPTION_PASS_PHRASE = null;

	/**
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION IV
	 *
	 * Value = base64_encode(openssl_random_pseudo_bytes(16))
	 * Example: public static $ENCRYPTION_IV = 'HnPG5az9Xaxam9G9tMuRaw==';
	 *
	 * @var null|string
	 */
	public static $ENCRYPTION_IV = null;

	/* MySql Session config */
	public static $mySqlServerHostname = '';
	public static $mySqlServerPort = 3306;
	public static $mySqlServerUsername = '';
	public static $mySqlServerPassword = '';
	public static $mySqlServerDB = '';
	public static $mySqlServerTable = '';

	/* PostgreSql Session config */
	public static $pgSqlServerHostname = '';
	public static $pgSqlServerPort = 5432;
	public static $pgSqlServerUsername = null;
	public static $pgSqlServerPassword = null;
	public static $pgSqlServerDB = '';
	public static $pgSqlServerTable = '';

	/* MongoDb Session config */
	public static $mongoDbServerHostname = '';
	public static $mongoDbServerPort = 27017;
	public static $mongoDbServerUsername = null;
	public static $mongoDbServerPassword = null;
	public static $mongoDbServerDB = '';
	public static $mongoDbServerCollection = '';

	/* Redis Session config */
	public static $redisServerHostname = '';
	public static $redisServerPort = 6379;
	public static $redisServerUsername = null;
	public static $redisServerPassword = null;
	public static $redisServerDB = 0;

	/* Memcached Session config */
	public static $memcachedServerHostname = '';
	public static $memcachedServerPort = 11211;

	/**
	 * Session Id Cookie name
	 *
	 * @var string
	 */
	public static $sessionName = 'PHPSESSID'; // Default

	/**
	 * Session Data Cookie name; For cookie as container
	 *
	 * @var string
	 */
	public static $sessionDataName = 'PHPSESSDATA';

	/**
	 * Session Life
	 *
	 * @var integer
	 */
	public static $sessionMaxLifetime = null;

	/**
	 * File Session options
	 * Example: public static $sessionSavePath = '/tmp';
	 *
	 * @var null|string
	 */
	public static $sessionSavePath = null;

	/**
	 * Session Handler mode
	 *
	 * @var null|string
	 */
	public static $sessionMode = null;

	/**
	 * Session Start function argument
	 *
	 * @var null|array
	 */
	public static $options = null;

	/**
	 * Session handler Container
	 *
	 * @var null|SessionContainerInterface
	 */
	public static $sessionContainer = null;

	/**
	 * Validate settings
	 *
	 * @return void
	 */
	private static function validateSettings(): void
	{
		// sessionMode validation
		if (
			!in_array(
				needle: self::$sessionMode,
				haystack: ['File', 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached', 'Cookie']
			)
		) {
			die('Invalid "sessionMode"');
		}

		// Required param validations
		if (empty(self::$sessionName)) {
			die('Invalid "sessionName"');
		}
		if (self::$sessionMode === 'Cookie' && empty(self::$sessionDataName)) {
			die('Invalid "sessionDataName"');
		}

		// Required parameters as per sessionMode
		switch (self::$sessionMode) {
			case 'Cookie':
				// Encryption compulsary for saving data as cookie
				if (empty(self::$ENCRYPTION_PASS_PHRASE)) {
					die('Invalid "ENCRYPTION_PASS_PHRASE"');
				}
				if (empty(self::$ENCRYPTION_IV)) {
					die('Invalid "ENCRYPTION_IV"');
				}
				break;
			case 'MySql':
				if (empty(self::$mySqlServerHostname)) {
					die('Invalid "mySqlServerHostname"');
				}
				if (empty(self::$mySqlServerPort)) {
					die('Invalid "mySqlServerPort"');
				}
				if (empty(self::$mySqlServerUsername)) {
					die('Invalid "mySqlServerUsername"');
				}
				if (empty(self::$mySqlServerPassword)) {
					die('Invalid "mySqlServerPassword"');
				}
				if (empty(self::$mySqlServerDB)) {
					die('Invalid "mySqlServerDB"');
				}
				if (empty(self::$mySqlServerTable)) {
					die('Invalid "mySqlServerTable"');
				}
				break;
			case 'PostgreSql':
				if (empty(self::$pgSqlServerHostname)) {
					die('Invalid "pgSqlServerHostname"');
				}
				if (empty(self::$pgSqlServerPort)) {
					die('Invalid "pgSqlServerPort"');
				}
				if (empty(self::$pgSqlServerDB)) {
					die('Invalid "pgSqlServerDB"');
				}
				if (empty(self::$pgSqlServerTable)) {
					die('Invalid "pgSqlServerTable"');
				}
				break;
			case 'MongoDb':
				if (empty(self::$mongoDbServerHostname)) {
					die('Invalid "mongoDbServerHostname"');
				}
				if (empty(self::$mongoDbServerPort)) {
					die('Invalid "mongoDbServerPort"');
				}
				if (empty(self::$mongoDbServerDB)) {
					die('Invalid "mongoDbServerDB"');
				}
				if (empty(self::$mongoDbServerCollection)) {
					die('Invalid "mongoDbServerCollection"');
				}
				break;
			case 'Redis':
				if (empty(self::$redisServerHostname)) {
					die('Invalid "redisServerHostname"');
				}
				if (empty(self::$redisServerPort)) {
					die('Invalid "redisServerPort"');
				}
				if (empty(self::$redisServerDB) && self::$redisServerDB != 0) {
					die('Invalid "redisServerDB"');
				}
				break;
			case 'Memcached':
				if (empty(self::$memcachedServerHostname)) {
					die('Invalid "memcachedServerHostname"');
				}
				if (empty(self::$memcachedServerPort)) {
					die('Invalid "memcachedServerPort"');
				}
				break;
		}
	}

	/**
	 * Initialize container
	 *
	 * @return void
	 */
	private static function initContainer(): void
	{
		// Initialize Container
		$containerClassName = 'Microservices\\App\\SessionHandlers\\Container\\'
			. self::$sessionMode . 'BasedSessionContainer';
		self::$sessionContainer = new $containerClassName();

		// Setting required common parameters
		self::$sessionContainer->sessionOptions = self::$options;
		self::$sessionContainer->sessionName = self::$sessionName;
		self::$sessionContainer->sessionMaxLifetime = (int)self::$sessionMaxLifetime;

		// Setting required parameters as per sessionMode
		switch (self::$sessionMode) {
			case 'MySql':
				self::$sessionContainer->mySqlServerHostname = self::$mySqlServerHostname;
				self::$sessionContainer->mySqlServerPort = (int)self::$mySqlServerPort;
				self::$sessionContainer->mySqlServerUsername = self::$mySqlServerUsername;
				self::$sessionContainer->mySqlServerPassword = self::$mySqlServerPassword;
				self::$sessionContainer->mySqlServerDB = self::$mySqlServerDB;
				self::$sessionContainer->mySqlServerTable = self::$mySqlServerTable;
				break;
			case 'PostgreSql':
				self::$sessionContainer->pgSqlServerHostname = self::$pgSqlServerHostname;
				self::$sessionContainer->pgSqlServerPort = (int)self::$pgSqlServerPort;
				self::$sessionContainer->pgSqlServerUsername = self::$pgSqlServerUsername;
				self::$sessionContainer->pgSqlServerPassword = self::$pgSqlServerPassword;
				self::$sessionContainer->pgSqlServerDB = self::$pgSqlServerDB;
				self::$sessionContainer->pgSqlServerTable = self::$pgSqlServerTable;
				break;
			case 'MongoDb':
				self::$sessionContainer->mongoDbServerHostname = self::$mongoDbServerHostname;
				self::$sessionContainer->mongoDbServerPort = (int)self::$mongoDbServerPort;
				self::$sessionContainer->mongoDbServerUsername = self::$mongoDbServerUsername;
				self::$sessionContainer->mongoDbServerPassword = self::$mongoDbServerPassword;
				self::$sessionContainer->mongoDbServerDB = self::$mongoDbServerDB;
				self::$sessionContainer->mongoDbServerCollection = self::$mongoDbServerCollection;
				break;
			case 'Redis':
				self::$sessionContainer->redisServerHostname = self::$redisServerHostname;
				self::$sessionContainer->redisServerPort = (int)self::$redisServerPort;
				self::$sessionContainer->redisServerUsername = self::$redisServerUsername;
				self::$sessionContainer->redisServerPassword = self::$redisServerPassword;
				self::$sessionContainer->redisServerDB = self::$redisServerDB;
				break;
			case 'Memcached':
				self::$sessionContainer->memcachedServerHostname = self::$memcachedServerHostname;
				self::$sessionContainer->memcachedServerPort = (int)self::$memcachedServerPort;
				break;
			case 'Cookie':
				self::$sessionContainer->sessionDataName = self::$sessionDataName;
				break;
		}

		// Setting encryption parameters
		if (
			!empty(self::$ENCRYPTION_PASS_PHRASE)
			&& !empty(self::$ENCRYPTION_IV)
		) {
			self::$sessionContainer->passphrase = base64_decode(
				string: self::$ENCRYPTION_PASS_PHRASE
			);
			self::$sessionContainer->iv = base64_decode(
				string: self::$ENCRYPTION_IV
			);
		}
	}

	/**
	 * Initialize session_set_save_handler process
	 *
	 * @return void
	 */
	private static function initProcess(): void
	{
		// Initialize container
		self::initContainer();

		$customSessionHandler = new CustomSessionHandler(
			container: self::$sessionContainer
		);
		$customSessionHandler->sessionName = self::$sessionName;
		if (self::$sessionMode === 'Cookie') {
			$customSessionHandler->sessionDataName = self::$sessionDataName;
		}
		session_set_save_handler($customSessionHandler, true);
	}

	/**
	 * Generates session options argument
	 *
	 * @param array $options Options
	 *
	 * @return void
	 */
	private static function setOptions($options = []): void
	{
		if (isset($options['name'])) {
			self::$sessionName = $options['name'];
		}

		if (isset($options['gc_maxlifetime'])) {
			self::$sessionMaxLifetime = (int)$options['gc_maxlifetime'];
		} else {
			self::$sessionMaxLifetime = (int)Constant::$TOKEN_EXPIRY_TIME;
		}

		self::$options = [ // always required.
			'use_strict_mode' => true,
			'name' => self::$sessionName,
			'serialize_handler' => 'php_serialize',
			'lazy_write' => true,
			'gc_maxlifetime' => (int)self::$sessionMaxLifetime,
			'cookie_lifetime' => 0,
			'cookie_path' => '/',
			'cookie_domain' => '',
			'cookie_secure' => (
				!in_array(
					'localhost',
					explode('.', self::$sessionDomain)
				) ? true : false
			),
			'cookie_httponly' => true,
			'cookie_samesite' => 'Strict'
		];

		if (self::$sessionMode === 'File') {
			self::$options['save_path'] = self::$sessionSavePath;
		}

		if (!empty($options)) {
			foreach ($options as $option => $value) {
				if (
					in_array(
						needle: $option,
						haystack: ['name', 'serialize_handler', 'gc_maxlifetime']
					)
				) {
					// Skip option
					continue;
				}
				self::$options[$option] = $value;
			}
		}
	}

	/**
	 * Initialize session handler
	 *
	 * @param string $sessionMode File/MySql/Cookie
	 * @param array  $options     Options
	 *
	 * @return void
	 */
	public static function initSessionHandler($sessionMode, $options = []): void
	{
		$env = parse_ini_file(filename: Constant::$ROOT
			. DIRECTORY_SEPARATOR . '.env.session'
		);
		foreach ($env as $var => $value) {
			self::$$var = $value;
		}

		self::$sessionMode = $sessionMode;

		// Set options from php.ini if not set in this class
		if (empty(self::$sessionName)) {
			self::$sessionName = session_name();
		}
		if (self::$sessionMode === 'File') {
			if (empty(self::$sessionSavePath)) {
				self::$sessionSavePath = (session_save_path()
					? session_save_path() : sys_get_temp_dir()) . '/session-files';
			}
			if (strpos(self::$sessionSavePath, '/') !== 0) {
				self::$sessionSavePath =
					__DIR__ . DIRECTORY_SEPARATOR . self::$sessionSavePath;
			}
		}

		// Comment this call once you are done with validating settings part
		self::validateSettings();

		// Initialize
		self::setOptions(options: $options);
		self::initProcess();
	}

	/**
	 * Start session in read only mode
	 *
	 * @return void
	 */
	public static function sessionStartReadonly(): bool
	{
		if (
			isset($_COOKIE[self::$sessionName])
			&& !empty($_COOKIE[self::$sessionName])
		) {
			$options = self::$options;
			$options['read_and_close'] = true;

			self::$sessionContainer->sessionOptions = $options;
			return session_start(options: $options);
		}
		return false;
	}

	/**
	 * Start session in read/write mode
	 *
	 * @return bool
	 */
	public static function sessionStartReadWrite(): bool
	{
		self::$sessionContainer->sessionOptions = self::$options;
		return session_start(options: self::$options);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param string $sessionId Session ID
	 *
	 * @return bool
	 */
	public static function deleteSession($sessionId): bool
	{
		return self::$sessionContainer->deleteSession($sessionId);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param array $sessionIds Session IDs
	 *
	 * @return void
	 */
	public static function deleteSessions($sessionIds): void
	{
		for ($i = 0, $iCount = count($sessionIds); $i < $iCount; $i++) {
			self::deleteSession($sessionIds[$i]);
		}
	}
}
