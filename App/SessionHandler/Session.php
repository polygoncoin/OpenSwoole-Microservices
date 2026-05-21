<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   Openswoole-Microservices
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
 * @package   Openswoole-Microservices
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
	public $sessionDomain = null;

	/**
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION PASS PHRASE
	 *
	 * Value = base64_encode(openssl_random_pseudo_bytes(32))
	 * Example: public $ENCRYPTION_PASS_PHRASE =
	 * 'H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c=';
	 *
	 * @var null|string
	 */
	public $ENCRYPTION_PASS_PHRASE = null;

	/**
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION IV
	 *
	 * Value = base64_encode(openssl_random_pseudo_bytes(16))
	 * Example: public $ENCRYPTION_IV = 'HnPG5az9Xaxam9G9tMuRaw==';
	 *
	 * @var null|string
	 */
	public $ENCRYPTION_IV = null;

	/**
	 * Session id Cookie name
	 *
	 * @var string
	 */
	public $sessionName = 'PHPSESSID'; // Default

	/**
	 * Session Data Cookie name; For cookie as container
	 *
	 * @var string
	 */
	public $sessionDataName = 'PHPSESSDATA';

	/**
	 * Session Life
	 *
	 * @var integer
	 */
	public $sessionMaxLifetime = null;

	/**
	 * File Session optionArr
	 * Example: public $sessionSavePath = '/tmp';
	 *
	 * @var null|string
	 */
	public $sessionSavePath = null;

	/**
	 * Session mode
	 *
	 * @var null|string
	 */
	public $sessionMode = null;

	/**
	 * Customer Data
	 *
	 * @var null|array
	 */
	public $customerData = null;

	/**
	 * Session Start function argument
	 *
	 * @var null|array
	 */
	public $optionArr = null;

	/**
	 * Session handler Container
	 *
	 * @var null|SessionContainerInterface
	 */
	public $sessionContainer = null;

	/**
	 * Session initProcess function initialized
	 *
	 * @var bool
	 */
	public $initProcessInitialized = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	/**
	 * Initialize container
	 *
	 * @return void
	 */
	private function initContainer(): void
	{
		// Initialize Container
		$containerClassName = 'Microservices\\App\\SessionHandler\\Container\\'
			. $this->sessionMode . 'BasedSessionContainer';
		$this->sessionContainer = new $containerClassName();

		// Setting required common parameters
		$this->sessionContainer->sessionOptionArr = $this->optionArr;
		$this->sessionContainer->sessionName = $this->sessionName;
		$this->sessionContainer->sessionMaxLifetime = (int)$this->sessionMaxLifetime;

		$sessionServerHostname = getenv(name: $this->customerData['session_server_hostname']);
		$sessionServerPort = (int)getenv(name: $this->customerData['session_server_port']);
		$sessionServerUsername = getenv(name: $this->customerData['session_server_username']);
		$sessionServerPassword = getenv(name: $this->customerData['session_server_password']);
		$sessionServerDatabase = getenv(name: $this->customerData['session_server_db']);
		$sessionServerTable = getenv(name: $this->customerData['session_server_table']);

		// Setting required parameters as per session Mode / Type
		switch ($this->sessionMode) {
			case 'MySql':
				$this->sessionContainer->mySqlServerHostname = $sessionServerHostname;
				$this->sessionContainer->mySqlServerPort = $sessionServerPort;
				$this->sessionContainer->mySqlServerUsername = $sessionServerUsername;
				$this->sessionContainer->mySqlServerPassword = $sessionServerPassword;
				$this->sessionContainer->mySqlServerDatabase = $sessionServerDatabase;
				$this->sessionContainer->mySqlServerTable = $sessionServerTable;
				break;
			case 'PostgreSql':
				$this->sessionContainer->pgSqlServerHostname = $sessionServerHostname;
				$this->sessionContainer->pgSqlServerPort = $sessionServerPort;
				$this->sessionContainer->pgSqlServerUsername = $sessionServerUsername;
				$this->sessionContainer->pgSqlServerPassword = $sessionServerPassword;
				$this->sessionContainer->pgSqlServerDatabase = $sessionServerDatabase;
				$this->sessionContainer->pgSqlServerTable = $sessionServerTable;
				break;
			case 'MongoDb':
				$this->sessionContainer->mongoDbServerHostname = $sessionServerHostname;
				$this->sessionContainer->mongoDbServerPort = $sessionServerPort;
				$this->sessionContainer->mongoDbServerUsername = $sessionServerUsername;
				$this->sessionContainer->mongoDbServerPassword = $sessionServerPassword;
				$this->sessionContainer->mongoDbServerDatabase = $sessionServerDatabase;
				$this->sessionContainer->mongoDbServerCollection = $sessionServerTable;
				break;
			case 'Redis':
				$this->sessionContainer->redisServerHostname = $sessionServerHostname;
				$this->sessionContainer->redisServerPort = $sessionServerPort;
				$this->sessionContainer->redisServerUsername = $sessionServerUsername;
				$this->sessionContainer->redisServerPassword = $sessionServerPassword;
				$this->sessionContainer->redisServerDatabase = $sessionServerDatabase;
				break;
			case 'Memcached':
				$this->sessionContainer->memcachedServerHostname = $sessionServerHostname;
				$this->sessionContainer->memcachedServerPort = $sessionServerPort;
				break;
			case 'Cookie':
				$this->sessionContainer->sessionDataName = $this->sessionDataName;
				break;
		}

		// Setting encryption parameters
		if (
			!empty($this->ENCRYPTION_PASS_PHRASE)
			&& !empty($this->ENCRYPTION_IV)
		) {
			$this->sessionContainer->passphrase = base64_decode(
				string: $this->ENCRYPTION_PASS_PHRASE
			);
			$this->sessionContainer->iv = base64_decode(
				string: $this->ENCRYPTION_IV
			);
		}
	}

	/**
	 * Initialize session_set_save_handler process
	 *
	 * @return void
	 */
	private function initProcess(): void
	{
		if ($this->initProcessInitialized) {
			return;
		}

		$this->sessionStartCheck();

		// Initialize container
		$this->initContainer();

		$customSessionHandler = new CustomSessionHandler(
			container: $this->sessionContainer
		);
		$customSessionHandler->sessionName = $this->sessionName;
		if ($this->sessionMode === 'Cookie') {
			$customSessionHandler->sessionDataName = $this->sessionDataName;
		}
		session_set_save_handler($customSessionHandler, true);

		$this->initProcessInitialized = true;
	}

	/**
	 * Generates session optionArr argument
	 *
	 * @param array $optionArr Options
	 *
	 * @return void
	 */
	private function setOptions($optionArr = []): void
	{
		if (isset($optionArr['name'])) {
			$this->sessionName = $optionArr['name'];
		}

		if (isset($optionArr['gc_maxlifetime'])) {
			$this->sessionMaxLifetime = (int)$optionArr['gc_maxlifetime'];
		} else {
			$this->sessionMaxLifetime = (int)Constant::$TOKEN_EXPIRY_TIME;
		}

		$this->optionArr = [ // always required.
			'use_strict_mode' => true,
			'name' => $this->sessionName,
			'serialize_handler' => 'php_serialize',
			'lazy_write' => true,
			'gc_maxlifetime' => (int)$this->sessionMaxLifetime,
			'cookie_lifetime' => 0,
			'cookie_path' => '/',
			'cookie_domain' => '',
			'cookie_secure' => (
				strpos(
					haystack: $this->sessionDomain,
					needle: 'localhost'
				) !== false
			) ? true : false,
			'cookie_httponly' => true,
			'cookie_samesite' => 'Strict'
		];

		if ($this->sessionMode === 'File') {
			$this->optionArr['save_path'] = $this->sessionSavePath;
		}

		if (!empty($optionArr)) {
			foreach ($optionArr as $option => $value) {
				if (
					in_array(
						needle: $option,
						haystack: ['name', 'serialize_handler', 'gc_maxlifetime']
					)
				) {
					// Skip option
					continue;
				}
				$this->optionArr[$option] = $value;
			}
		}
	}

	/**
	 * Initialize session handler
	 *
	 * @param array $customerData 
	 * @param array $options      Options
	 *
	 * @return void
	 */
	public function initSessionHandler($customerData, $options = []): void
	{
		$envFilename = '.env.session';
		$envDataArr = parse_ini_file(filename: ROOT . DIRECTORY_SEPARATOR . $envFilename);
		foreach ($envDataArr as $envVarName => $envVarValue) {
			putenv(assignment: "{$envVarName}={$envVarValue}");
		}

		$this->customerData = $customerData;
		$this->sessionMode = getenv(name: $this->customerData['session_server_type']);

		// Set optoptionsionArr from php.ini if not set in this class
		if (empty($this->sessionName)) {
			$this->sessionName = session_name();
		}
		if ($this->sessionMode === 'File') {
			if (empty($this->sessionSavePath)) {
				$this->sessionSavePath = (session_save_path()
					? session_save_path() : sys_get_temp_dir()) . '/session-files';
			}
			if (strpos($this->sessionSavePath, '/') !== 0) {
				$this->sessionSavePath =
					__DIR__ . DIRECTORY_SEPARATOR . $this->sessionSavePath;
			}
		}

		// Initialize
		$this->setOptions(optionArr: $options);
		$this->initProcess();
	}

	/**
	 * Close if Session is Active in write mode
	 *
	 * @return void
	 */
	public function sessionStartCheck(): void
	{
		if (isset($_SESSION)) {
			if (
				!isset($this->optionArr['read_and_close'])
				|| $this->optionArr['read_and_close'] !== true
			) {
				session_write_close();
			}
		}
	}

	/**
	 * Start session in read only mode
	 *
	 * @return bool
	 */
	public function sessionStartReadonly(): bool
	{
		if (
			isset($_COOKIE[$this->sessionName])
			&& !empty($_COOKIE[$this->sessionName])
		) {
			$this->sessionStartCheck();
			$this->optionArr['read_and_close'] = true;

			$this->sessionContainer->sessionOptionArr = $this->optionArr;
			return session_start(options: $this->optionArr);
		}
		return false;
	}

	/**
	 * Start session in read/write mode
	 *
	 * @return bool
	 */
	public function sessionStartReadWrite(): bool
	{
		$this->sessionContainer->sessionOptionArr = $this->optionArr;
		$this->sessionStartCheck();
		if (isset($this->optionArr['read_and_close'])) {
			unset($this->optionArr['read_and_close']);
		}

		return session_start(options: $this->optionArr);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param string $sessionId Session id
	 *
	 * @return bool
	 */
	public function deleteSession($sessionId): bool
	{
		return $this->sessionContainer->deleteSession($sessionId);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param array $sessionIds Session IDs
	 *
	 * @return void
	 */
	public function deleteSessions($sessionIds): void
	{
		for ($i = 0, $iCount = count($sessionIds); $i < $iCount; $i++) {
			$this->deleteSession($sessionIds[$i]);
		}
	}
}
