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
use Microservices\App\Environment;
use Microservices\App\Http;
use Microservices\App\SessionHandler\CustomSessionHandler;
use Microservices\App\SessionHandler\Containers\SessionContainerInterface;

/**
 * Custom Session Handler Config
 * php version 7
 *
 * @category  SessionHandler_Config
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
	 * Http Cookies
	 * 
	 * @var null|array
	 */
	public $httpCookie = null;

	/**
	 * Environment data
	 * 
	 * @var null|Environment
	 */
	public $Env = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
		$this->Env = Env::$config[$this->httpObject->httpReqData['active']['customerId']];
		$this->httpCookie = $this->httpObject->httpReqData['header']['cookie'];
	}

	/**
	 * Initialize container
	 * 
	 * @return SessionContainerInterface
	 */
	private function getSessionContainer(): SessionContainerInterface
	{
		// Initialize Container
		$containerClassName = 'Microservices\\App\\SessionHandler\\Containers\\'
			. ucfirst($this->Env->SESSION_STORE_MODE) . 'BasedSessionContainer';
		$this->sessionContainer = new $containerClassName();

		// Setting encryption parameters
		$this->sessionContainer->SESSION_ENCRYPTION_PASS_PHRASE = base64_decode(
			string: $this->Env->SESSION_ENCRYPTION_PASS_PHRASE
		);

		$this->sessionContainer->SESSION_ENCRYPTION_IV = base64_decode(
			string: $this->Env->SESSION_ENCRYPTION_IV
		);

		$this->sessionContainer->sessionName = $this->Env->SESSION_COOKIE_NAME;
		$this->sessionContainer->Env = $this->Env;
		$this->sessionContainer->httpObject = &$this->httpObject;

		// Setting required parameters as per session Mode / Type
		switch ($this->Env->SESSION_STORE_MODE) {
			case 'MySql':
			case 'PostgreSql':
			case 'MongoDb':
				$this->sessionContainer->sessionServerHost = $this->Env->SESSION_HOST;
				$this->sessionContainer->sessionServerPort = $this->Env->SESSION_PORT;
				$this->sessionContainer->sessionServerUser = $this->Env->SESSION_USER;
				$this->sessionContainer->sessionServerPassword = $this->Env->SESSION_PASSWORD;
				$this->sessionContainer->sessionServerDb = $this->Env->SESSION_DB;
				$this->sessionContainer->sessionServerTable = $this->Env->SESSION_TABLE;
				break;
			case 'Redis':
				$this->sessionContainer->sessionServerHost = $this->Env->SESSION_HOST;
				$this->sessionContainer->sessionServerPort = $this->Env->SESSION_PORT;
				$this->sessionContainer->sessionServerUser = $this->Env->SESSION_USER;
				$this->sessionContainer->sessionServerPassword = $this->Env->SESSION_PASSWORD;
				$this->sessionContainer->sessionServerDb = $this->Env->SESSION_DB;
				break;
			case 'Memcached':
				$this->sessionContainer->sessionServerHost = $this->Env->SESSION_HOST;
				$this->sessionContainer->sessionServerPort = $this->Env->SESSION_PORT;
				break;
			case 'Cookie':
                $this->sessionContainer->sessionDataCookieName = $this->Env->SESSION_DATA_COOKIE_NAME;
				break;
		}

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
			'name' => $this->Env->SESSION_COOKIE_NAME,
			'serialize_handler' => 'php_serialize',
			'lazy_write' => Constant::$TRUE,
			'cookie_lifetime' => (int)$this->Env->SESSION_LIFETIME,
			'cookie_path' => $this->Env->SESSION_COOKIE_PATH,
			'cookie_secure' => (bool)$this->Env->SESSION_COOKIE_SECURE,
			'cookie_httponly' => (bool)$this->Env->SESSION_COOKIE_HTTPONLY,
			'cookie_samesite' => $this->Env->SESSION_COOKIE_SAMESITE,
		];

		if ($this->Env->SESSION_STORE_MODE === 'File') {
			$options['save_path'] = $this->Env->SESSION_STORE_PATH;
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
			!isset($this->httpCookie[$this->Env->SESSION_COOKIE_NAME])
			|| empty($this->httpCookie[$this->Env->SESSION_COOKIE_NAME])
		) {
			return Constant::$FALSE;
		}

		$currentSessionId = $this->httpCookie[$this->Env->SESSION_COOKIE_NAME];
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
			isset($this->httpCookie[$this->Env->SESSION_COOKIE_NAME])
			|| !empty($this->httpCookie[$this->Env->SESSION_COOKIE_NAME])
		) {
			$currentSessionId = $this->httpCookie[$this->Env->SESSION_COOKIE_NAME];
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
