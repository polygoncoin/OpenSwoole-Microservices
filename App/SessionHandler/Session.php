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
use Microservices\App\Http;
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
	 * Session customer id
	 * 
	 * @var null|int
	 */
	public $customerId = null;

	/**
	 * Session cookie name
	 * 
	 * @var null|string
	 */
	public $sessionName = null;

	/**
	 * Session handler Container
	 * 
	 * @var null|SessionContainerInterface
	 */
	public $sessionContainer = null;

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
		Env::loadEnv(
			customerId: $this->customerId
		);
		$this->customerId = $this->httpObject->httpReqData['current']['customerId'];
		$this->sessionName = Env::$config[$this->customerId]->SESSION_COOKIE_NAME;
	}

	/**
	 * Initialize container
	 * 
	 * @return SessionContainerInterface
	 */
	private function getSessionContainer(): SessionContainerInterface
	{
		// Initialize Container
		$containerClassName = 'Microservices\\App\\SessionHandler\\Container\\'
			. $this->sessionMode . 'BasedSessionContainer';
		$this->sessionContainer = new $containerClassName(
			httpObject: $this->httpObject
		);

		// Setting required parameters as per session Mode / Type
		switch ($this->sessionMode) {
			case 'MySql':
				$this->sessionContainer->mySqlServerHost = Env::$config[$this->customerId]->SESSION_MYSQL_HOST;
				$this->sessionContainer->mySqlServerPort = Env::$config[$this->customerId]->SESSION_MYSQL_PORT;
				$this->sessionContainer->mySqlServerUser = Env::$config[$this->customerId]->SESSION_MYSQL_USER;
				$this->sessionContainer->mySqlServerPassword = Env::$config[$this->customerId]->SESSION_MYSQL_PASSWORD;
				$this->sessionContainer->mySqlServerDb = Env::$config[$this->customerId]->SESSION_MYSQL_DB;
				$this->sessionContainer->mySqlServerTable = Env::$config[$this->customerId]->SESSION_MYSQL_TABLE;
				break;
			case 'PostgreSql':
				$this->sessionContainer->pgSqlServerHost = Env::$config[$this->customerId]->SESSION_PGSQL_HOST;
				$this->sessionContainer->pgSqlServerPort = Env::$config[$this->customerId]->SESSION_PGSQL_PORT;
				$this->sessionContainer->pgSqlServerUser = Env::$config[$this->customerId]->SESSION_PGSQL_USER;
				$this->sessionContainer->pgSqlServerPassword = Env::$config[$this->customerId]->SESSION_PGSQL_PASSWORD;
				$this->sessionContainer->pgSqlServerDb = Env::$config[$this->customerId]->SESSION_PGSQL_DB;
				$this->sessionContainer->pgSqlServerTable = Env::$config[$this->customerId]->SESSION_PGSQL_TABLE;
				break;
			case 'MongoDb':
				$this->sessionContainer->mongoDbServerHost = Env::$config[$this->customerId]->SESSION_MONGO_HOST;
				$this->sessionContainer->mongoDbServerPort = Env::$config[$this->customerId]->SESSION_MONGO_PORT;
				$this->sessionContainer->mongoDbServerUser = Env::$config[$this->customerId]->SESSION_MONGO_USER;
				$this->sessionContainer->mongoDbServerPassword = Env::$config[$this->customerId]->SESSION_MONGO_PASSWORD;
				$this->sessionContainer->mongoDbServerDb = Env::$config[$this->customerId]->SESSION_MONGO_DB;
				$this->sessionContainer->mongoDbServerCollection = Env::$config[$this->customerId]->SESSION_MONGO_TABLE;
				break;
			case 'Redis':
				$this->sessionContainer->redisServerHost = Env::$config[$this->customerId]->SESSION_REDIS_HOST;
				$this->sessionContainer->redisServerPort = Env::$config[$this->customerId]->SESSION_REDIS_PORT;
				$this->sessionContainer->redisServerUser = Env::$config[$this->customerId]->SESSION_REDIS_USER;
				$this->sessionContainer->redisServerPassword = Env::$config[$this->customerId]->SESSION_REDIS_PASSWORD;
				$this->sessionContainer->redisServerDb = Env::$config[$this->customerId]->SESSION_REDIS_DB;
				break;
			case 'Memcached':
				$this->sessionContainer->memcachedServerHost = Env::$config[$this->customerId]->SESSION_MEMCACHE_HOST;
				$this->sessionContainer->memcachedServerPort = Env::$config[$this->customerId]->SESSION_MEMCACHE_PORT;
				break;
			case 'Cookie':
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
		
		$this->sessionContainer->customerId = $this->customerId;
		$this->sessionContainer->sessionName = $this->sessionName;

		return $this->sessionContainer;
	}

	/**
	 * Generates session optionArray argument
	 * 
	 * @param bool $readonly Readonly mode
	 * 
	 * @return array
	 */
	private function getSessionOptions(
		$readonly
	): array {
		$options = [ // always required.
			'use_strict_mode' => Constant::$TRUE,
			'use_cookies' => Constant::$TRUE,
			'name' => Env::$config[$this->customerId]->SESSION_COOKIE_NAME,
			'serialize_handler' => 'php_serialize',
			'lazy_write' => Constant::$TRUE,
			'cookie_lifetime' => (int)Env::$config[$this->customerId]->SESSION_LIFETIME,
			'cookie_path' => Env::$config[$this->customerId]->SESSION_COOKIE_PATH,
			'cookie_secure' => (bool)Env::$config[$this->customerId]->SESSION_COOKIE_SECURE,
			'cookie_httponly' => (bool)Env::$config[$this->customerId]->SESSION_COOKIE_HTTPONLY,
			'cookie_samesite' => Env::$config[$this->customerId]->SESSION_COOKIE_SAMESITE,
		];

		if ($this->sessionMode === 'File') {
			$options['save_path'] = Env::$config[$this->customerId]->SESSION_STORE_PATH;
		}

		if ($readonly === Constant::$TRUE) {
			$options['read_and_close'] = Constant::$TRUE;
		}

		// Setting required common parameters
		$this->sessionContainer->sessionOptions = $options;

		return $options;
	}

	/**
	 * Initialize session handler
	 * 
	 * @return void
	 */
	public function initSessionHandler(): void
	{
		$this->sessionMode = Env::$config[$this->customerId]->SESSION_STORE_MODE;

		// Initialize container
		$this->sessionContainer = $this->getSessionContainer();

		$customSessionHandler = new CustomSessionHandler(
			container: $this->sessionContainer
		);

		session_set_save_handler(
			$customSessionHandler,
			Constant::$TRUE
		);

	}

	/**
	 * Close if Session is Active in write mode
	 * 
	 * @return void
	 */
	public function closeActiveSession(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
	}

	/**
	 * Start session in read only mode
	 * 
	 * @return bool
	 */
	public function startReadonly(): bool
	{
		$this->closeActiveSession();

		if (
			!isset($this->httpObject->httpReqData['header']['cookie'][$this->sessionName])
			|| empty($this->httpObject->httpReqData['header']['cookie'][$this->sessionName])
		) {
			return Constant::$FALSE;
		}

		$currentSessionId = $this->httpObject->httpReqData['header']['cookie'][$this->sessionName];
		session_id($currentSessionId);

		return session_start(
			options: $this->getSessionOptions(
				readonly: Constant::$TRUE
			)
		);
	}

	/**
	 * Start session in read/write mode
	 * 
	 * @return bool
	 */
	public function startReadWrite(): bool
	{
		$this->closeActiveSession();

		if (
			isset($this->httpObject->httpReqData['header']['cookie'][$this->sessionName])
			|| !empty($this->httpObject->httpReqData['header']['cookie'][$this->sessionName])
		) {
			$currentSessionId = $this->httpObject->httpReqData['header']['cookie'][$this->sessionName];
			session_id($currentSessionId);
		}

		return session_start(
			options: $this->getSessionOptions(
				readonly: Constant::$FALSE
			)
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
