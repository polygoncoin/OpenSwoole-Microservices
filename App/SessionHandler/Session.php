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
use Microservices\App\Env;
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
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION PASS PHRASE
	 *
	 * Value = base64_encode(openssl_random_pseudo_bytes(32))
	 * Example: public $sessionEncryptionPassPhrase =
	 * 'H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c=';
	 *
	 * @var null|string
	 */
	public $sessionEncryptionPassPhrase = null;

	/**
	 * SET THESE TO ENABLE ENCRYPTION
	 * ENCRYPTION IV
	 *
	 * Value = base64_encode(openssl_random_pseudo_bytes(16))
	 * Example: public $sessionEncryptionIv = 'HnPG5az9Xaxam9G9tMuRaw==';
	 *
	 * @var null|string
	 */
	public $sessionEncryptionIv = null;

	/**
	 * Session mode
	 *
	 * @var null|string
	 */
	public $sessionMode = null;

	/**
	 * Session Start function argument
	 *
	 * @var null|array
	 */
	public $optionArray = null;

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
	 * Session customer id
	 *
	 * @var bool
	 */
	public $customerId = null;

	/**
	 * Constructor
	 * 
	 * @param int $customerId Customer Id
	 */
	public function __construct($customerId)
	{
		$this->customerId = $customerId;
		Env::loadEnv(
			customerId: $customerId
		);
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
		$this->sessionContainer->sessionOptionArray = $this->optionArray;

		// Setting required parameters as per session Mode / Type
		switch ($this->sessionMode) {
			case 'MySql':
				$this->sessionContainer->sessionServerHost = Env::$config[$this->customerId]->SESSION_MYSQL_HOST;
				$this->sessionContainer->sessionServerPort = Env::$config[$this->customerId]->SESSION_MYSQL_PORT;
				$this->sessionContainer->sessionServerUser = Env::$config[$this->customerId]->SESSION_MYSQL_USER;
				$this->sessionContainer->sessionServerPassword = Env::$config[$this->customerId]->SESSION_MYSQL_PASSWORD;
				$this->sessionContainer->sessionServerDb = Env::$config[$this->customerId]->SESSION_MYSQL_DB;
				$this->sessionContainer->sessionServerTable = Env::$config[$this->customerId]->SESSION_MYSQL_TABLE;
				break;
			case 'PostgreSql':
				$this->sessionContainer->pgSqlServerHostname = Env::$config[$this->customerId]->SESSION_PGSQL_HOST;
				$this->sessionContainer->pgSqlServerPort = Env::$config[$this->customerId]->SESSION_PGSQL_PORT;
				$this->sessionContainer->pgSqlServerUsername = Env::$config[$this->customerId]->SESSION_PGSQL_USER;
				$this->sessionContainer->pgSqlServerPassword = Env::$config[$this->customerId]->SESSION_PGSQL_PASSWORD;
				$this->sessionContainer->pgSqlServerDatabase = Env::$config[$this->customerId]->SESSION_PGSQL_DB;
				$this->sessionContainer->pgSqlServerTable = Env::$config[$this->customerId]->SESSION_PGSQL_TABLE;
				break;
			case 'MongoDb':
				$this->sessionContainer->mongoDbServerHostname = Env::$config[$this->customerId]->SESSION_MONGO_HOST;
				$this->sessionContainer->mongoDbServerPort = Env::$config[$this->customerId]->SESSION_MONGO_PORT;
				$this->sessionContainer->mongoDbServerUsername = Env::$config[$this->customerId]->SESSION_MONGO_USER;
				$this->sessionContainer->mongoDbServerPassword = Env::$config[$this->customerId]->SESSION_MONGO_PASSWORD;
				$this->sessionContainer->mongoDbServerDatabase = Env::$config[$this->customerId]->SESSION_MONGO_DB;
				$this->sessionContainer->mongoDbServerCollection = Env::$config[$this->customerId]->SESSION_MONGO_TABLE;
				break;
			case 'Redis':
				$this->sessionContainer->redisServerHostname = Env::$config[$this->customerId]->SESSION_REDIS_HOST;
				$this->sessionContainer->redisServerPort = Env::$config[$this->customerId]->SESSION_REDIS_PORT;
				$this->sessionContainer->redisServerUsername = Env::$config[$this->customerId]->SESSION_REDIS_USER;
				$this->sessionContainer->redisServerPassword = Env::$config[$this->customerId]->SESSION_REDIS_PASSWORD;
				$this->sessionContainer->redisServerDatabase = Env::$config[$this->customerId]->SESSION_REDIS_DB;
				break;
			case 'Memcached':
				$this->sessionContainer->memcachedServerHostname = Env::$config[$this->customerId]->SESSION_MEMCACHE_HOST;
				$this->sessionContainer->memcachedServerPort = Env::$config[$this->customerId]->SESSION_MEMCACHE_PORT;
				break;
			case 'Cookie':
				$this->sessionContainer->sessionDataCookieName = Env::$config[$this->customerId]->SESSION_DATA_COOKIE_NAME;
				break;
		}

		// Setting encryption parameters
		if (
			!empty($this->sessionEncryptionPassPhrase)
			&& !empty($this->sessionEncryptionIv)
		) {
			$this->sessionContainer->passphrase = base64_decode(
				string: $this->sessionEncryptionPassPhrase
			);
			$this->sessionContainer->iv = base64_decode(
				string: $this->sessionEncryptionIv
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
		session_set_save_handler(
			$customSessionHandler,
			Constant::$TRUE
		);

		$this->initProcessInitialized = Constant::$TRUE;
	}

	/**
	 * Generates session optionArray argument
	 *
	 * @param array $optionArray Options
	 *
	 * @return void
	 */
	private function setOptions(
		$optionArray = []
	): void {
		$this->optionArray = [ // always required.
			'use_strict_mode' => Constant::$TRUE,
			'name' => Env::$config[$this->customerId]->SESSION_COOKIE_NAME,
			'serialize_handler' => 'php_serialize',
			'lazy_write' => Constant::$TRUE,
			'gc_maxlifetime' => Env::$config[$this->customerId]->SESSION_LIFETIME,
			'cookie_lifetime' => Env::$config[$this->customerId]->SESSION_LIFETIME,
			'cookie_path' => Env::$config[$this->customerId]->SESSION_COOKIE_PATH,
			'cookie_domain' => Env::$config[$this->customerId]->SESSION_COOKIE_DOMAIN,
			'cookie_secure' => Env::$config[$this->customerId]->SESSION_COOKIE_SECURE,
			'cookie_httponly' => Env::$config[$this->customerId]->SESSION_COOKIE_HTTPONLY,
			'cookie_samesite' => Env::$config[$this->customerId]->SESSION_COOKIE_SAMESITE,
		];

		if ($this->sessionMode === 'File') {
			$this->optionArray['save_path'] = Env::$config[$this->customerId]->SESSION_STORE_PATH;
		}

		if (!empty($optionArray)) {
			foreach ($optionArray as $option => $value) {
				if (
					in_array(
						needle: $option,
						haystack: ['name', 'serialize_handler', 'gc_maxlifetime'],
						strict: Constant::$TRUE
					)
				) {
					// Skip option
					continue;
				}
				$this->optionArray[$option] = $value;
			}
		}
	}

	/**
	 * Initialize session handler
	 *
	 * @param array $options Options
	 *
	 * @return void
	 */
	public function initSessionHandler(
		$options = []
	): void {
		$this->sessionMode = Env::$config[$this->customerId]->SESSION_STORE_MODE;

		// Initialize
		$this->setOptions(
			optionArray: $options
		);
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
				!isset($this->optionArray['read_and_close'])
				|| $this->optionArray['read_and_close'] !== Constant::$TRUE
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
			isset($_COOKIE[Env::$config[$this->customerId]->SESSION_COOKIE_NAME])
			&& !empty($_COOKIE[Env::$config[$this->customerId]->SESSION_COOKIE_NAME])
		) {
			$this->sessionStartCheck();
			$this->optionArray['read_and_close'] = Constant::$TRUE;

			$this->sessionContainer->sessionOptionArray = $this->optionArray;
			return session_start(
				options: $this->optionArray
			);
		}
		return Constant::$FALSE;
	}

	/**
	 * Start session in read/write mode
	 *
	 * @return bool
	 */
	public function sessionStartReadWrite(): bool
	{
		$this->sessionContainer->sessionOptionArray = $this->optionArray;
		$this->sessionStartCheck();
		if (isset($this->optionArray['read_and_close'])) {
			unset($this->optionArray['read_and_close']);
		}

		return session_start(
			options: $this->optionArray
		);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param string $sessionId Session id
	 *
	 * @return bool
	 */
	public function deleteSession(
		$sessionId
	): bool {
		return $this->sessionContainer->deleteSession(
			$sessionId
		);
	}

	/**
	 * For Custom Session Handler - Destroy a session
	 *
	 * @param array $sessionIds Session IDs
	 *
	 * @return void
	 */
	public function deleteSessions(
		$sessionIds
	): void {
		$indexCount = count(
			value: $sessionIds
		);
		for ($index = 0; $index < $indexCount; $index++) {
			$this->deleteSession(
				$sessionIds[$index]
			);
		}
	}
}
