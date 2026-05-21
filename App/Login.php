<?php

/**
 * Login
 * php version 8.3
 *
 * @category  Login
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheServerKey;
use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\SessionHandler\Session;

/**
 * Login
 * php version 8.3
 *
 * @category  Login
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Login
{
	/**
	 * Username for login
	 *
	 * @var null|string
	 */
	public $username = null;

	/**
	 * Password for login
	 *
	 * @var null|string
	 */
	public $password = null;

	/**
	 * Payload
	 *
	 * @var array
	 */
	private $payload = [];

	/**
	 * HTTP object
	 *
	 * @var null|Http
	 */
	private $http = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		if ($this->http->res !== null) {
			$this->http->initResponse();
		}

		return true;
	}

	/**
	 * Process
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function process(): mixed
	{
		// Check request method is POST
		if ($this->http->httpReqData['server']['httpMethod'] !== Constant::$POST) {
			throw new \Exception(
				message: 'Invalid request method',
				code: HttpStatus::$NotFound
			);
		}

		$this->loadPayload();
		$this->loadUserData();
		CommonFunction::checkPrivateRequestCidr(http: $this->http);
		$this->validatePassword();

		if (
			CommonFunction::isEnabled(http: $this->http, feature: 'enableRateLimitForUserPerIp')
			&& !empty($this->http->req->s['customerData']['rateLimitMaxUserPerIp'])
			&& !empty($this->http->req->s['customerData']['rateLimitMaxUserPerIpWindow'])
		) {
			$this->http->req->rateLimiter->checkRateLimit(
				rateLimitPrefix: Env::$rateLimitUserPerIpPrefix,
				rateLimitMaxRequest: $this->http->req->s['customerData']['rateLimitMaxUserPerIp'],
				rateLimitMaxRequestWindow: $this->http->req->s['customerData']['rateLimitMaxUserPerIpWindow'],
				rateLimitKey: $this->http->httpReqData['server']['httpRequestIP']
			);
		}

		if ($this->http->req->isPrivateSessionDomain) {
			$this->startSession();
		} elseif ($this->http->req->isPrivateTokenDomain) {
			$this->outputTokenData();
		} else {
			throw new \Exception(
				message: "Invalid domain: '{$this->http->httpReqData['server']['domainName']}' to login",
				code: HttpStatus::$BadRequest
			);
		}

		return true;
	}

	/**
	 * Load payload
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function loadPayload(): void
	{
		// Check request method is POST
		if ($this->http->httpReqData['server']['httpMethod'] !== Constant::$POST) {
			throw new \Exception(
				message: 'Invalid request method',
				code: HttpStatus::$NotFound
			);
		}

		$this->http->req->loadPayload();
		$this->payload = $this->http->req->dataDecode->get();

		// Check for required conditions variables
		foreach (['username', 'password'] as $value) {
			if (
				!isset($this->payload[$value])
				|| empty($this->payload[$value])
			) {
				throw new \Exception(
					message: 'Missing required parameters',
					code: HttpStatus::$NotFound
				);
			} else {
				$this->$value = $this->payload[$value];
			}
		}
	}

	/**
	 * Load User Data from cache
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function loadUserData(): void
	{
		$customerId = $this->http->req->customerId;
		$customerUserKey = CacheServerKey::customerUsername(
			customerId: $customerId,
			username: $this->payload['username']
		);
		// Redis - one can find the userId from customer username
		if (!$this->cacheExist(cacheKey: $customerUserKey)) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}

		$userData = json_decode(
			json: $this->cacheGet(
				cacheKey: $customerUserKey
			),
			associative: true
		);
		if (
			empty($userData['id'])
			|| empty($userData['id'])
		) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}
		$this->http->req->s['userData'] = $userData;
		$this->http->req->userId = $userData['id'];
		$this->http->req->groupId = $userData['group_id'];
	}

	/**
	 * Validates password from its hash present in cache
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validatePassword(): void
	{
		if (
			!empty($this->http->req->s['customerData']['rateLimitMaxUserLoginRequest'])
			&& !empty($this->http->req->s['customerData']['rateLimitMaxUserLoginRequestWindow'])
		) {
			$this->http->req->rateLimiter->checkRateLimit(
				rateLimitPrefix: Env::$rateLimitUserLoginPrefix,
				rateLimitMaxRequest: $this->http->req->s['customerData']['rateLimitMaxUserLoginRequest'],
				rateLimitMaxRequestWindow: $this->http->req->s['customerData']['rateLimitMaxUserLoginRequestWindow'],
				rateLimitKey: $this->http->httpReqData['server']['httpRequestIP'] . ':' . $this->username
			);
		}

		// get hash from cache and compares with password
		if (
			!password_verify(
				password: $this->password,
				hash: $this->http->req->s['userData']['password_hash']
			)
		) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}
	}

	/**
	 * Generates token
	 *
	 * @return array
	 */
	private function generateToken(): array
	{
		//generates a crypto-secure 64 characters long
		while (true) {
			$authId = bin2hex(string: random_bytes(length: 32));

			if (
				!$this->cacheExist(
					cacheKey: CacheServerKey::token(token: $authId)
				)
			) {
				$this->cacheSet(
					cacheKey: CacheServerKey::token(token: $authId),
					cacheValue: '{}',
					cacheExpire: Constant::$TOKEN_EXPIRY_TIME
				);
				$userTokenData = [
					'authId' => $authId,
					'authMode' => 'Token',
					'authTimestamp' => Env::$timestamp,
					'httpRequestHash' => $this->http->httpReqData['httpRequestHash']
				];
				break;
			}
		}

		foreach ($this->http->req->s['userData'] as $k => $v) {
			$userTokenData[$k] = $v;
		}

		$this->cacheSet(
			cacheKey: CacheServerKey::token(token: $userTokenData['authId']),
			cacheValue: json_encode(
				value: $userTokenData
			),
			cacheExpire: Constant::$TOKEN_EXPIRY_TIME
		);

		return $userTokenData;
	}

	/**
	 * Generates session
	 *
	 * @return array
	 */
	private function generateSession(): array
	{
		if ($this->http->req->session === null) {
			$this->http->req->session = new Session();
			$this->http->req->session->initSessionHandler(sessionMode: Env::$sessionMode, options: []);
		}
		$this->http->req->session->sessionStartReadWrite();
		$userSessionData = [
			'authId' => session_id(),
			'authMode' => 'Session',
			'authTimestamp' => Env::$timestamp,
			'httpRequestHash' => $this->http->httpReqData['httpRequestHash']
		];

		foreach ($this->http->req->s['userData'] as $k => $v) {
			$userSessionData[$k] = $v;
		}

		$_SESSION = $userSessionData;

		return $userSessionData;
	}

	/**
	 * Outputs active/newly generated token detail
	 *
	 * @return void
	 */
	private function outputTokenData(): void
	{
		$httpRequestHash = $this->http->httpReqData['httpRequestHash'];

		$customerUserTokenKey = null;
		$customerUserToken = null;
	
		$authFound = false;
		$authFoundData = [];

		$customerUserConcurrencyKey = null;
		$customerUserConcurrencyData = null;

		$customerUserTokenKey = CacheServerKey::customerUserToken(
			customerId: $this->http->req->customerId,
			userId: $this->http->req->userId
		);

		if ($this->cacheExist(cacheKey: $customerUserTokenKey)) {
			$customerUserToken = $this->cacheGet(
				cacheKey: $customerUserTokenKey
			);
		}

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
			$customerUserConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);

			if ($this->cacheExist(cacheKey: $customerUserConcurrencyKey)) {
				if ($this->http->req->session === null) {
					$this->http->req->session = new Session();
					$this->http->req->session->initSessionHandler(sessionMode: Env::$sessionMode, options: []);
				}
				$customerUserConcurrencyData = json_decode(
					json: $this->cacheGet(
						cacheKey: $customerUserConcurrencyKey
					),
					associative: true
				);

				foreach ($customerUserConcurrencyData as $authId => $authData) {
					if (
						$authData['authMode'] === 'Token'
						&& !$this->cacheExist(cacheKey: CacheServerKey::token(token: $authId))
					) {
						unset($customerUserConcurrencyData[$authId]);
						continue;
					}
					if (
						$authData['authMode'] === 'Session'
					) {
						$time = Env::$timestamp - $authFoundData['authTimestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							$this->http->req->session->deleteSession(sessionId: $authId);
							unset($customerUserConcurrencyData[$authId]);
							continue;
						}
					}
					if (
						$customerUserToken !== null
						&& $customerUserToken === $authId
						&& $authData['httpRequestHash'] === $httpRequestHash
					) {
						$authFoundData = $authData;
						$authFound = true;
					}
				}
			}
		} else {
			if (
				$customerUserToken !== null
				&& $this->cacheExist(cacheKey: CacheServerKey::token(token: $customerUserToken))
			) {
				$authId = $customerUserToken;
				$authData = $this->cacheGet(cacheKey: CacheServerKey::token(token: $customerUserToken));
				if ($authData['httpRequestHash'] === $httpRequestHash) {
					$authFoundData = $authData;
					$authFound = true;
				}
			}
		}

		if (!$authFound) {
			$authFoundData = $this->generateToken();
			$authFound = true;

			$this->cacheSet(
				cacheKey: $customerUserTokenKey,
				cacheValue: $authFoundData['authId'],
				cacheExpire: Constant::$TOKEN_EXPIRY_TIME
			);

			$customerUserConcurrencyData[$authFoundData['authId']] = $authFoundData;
		}

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
			if (count($customerUserConcurrencyData) >= Env::$maxConcurrentLogin) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$customerUserConcurrencyKey = $customerUserConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);
			$this->cacheSet(
				cacheKey: $customerUserConcurrencyKey,
				cacheValue: json_encode($customerUserConcurrencyData),
				cacheExpire: Env::$concurrentAccessInterval
			);
		}
		$this->updateDb(userData: $authFoundData);

		$time = Env::$timestamp - $authFoundData['authTimestamp'];
		$output = [
			'Token' => $authFoundData['authId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', (Constant::$TOKEN_EXPIRY_TIME - $time))
		];

		$this->outputDetail(output: $output);
	}

	/**
	 * Output detail
	 *
	 * @param array $output
	 *
	 * @return void
	 */
	private function outputDetail(&$output): void
	{
		$this->http->initResponse();
		$this->http->res->dataEncode->startObject();
		$this->http->res->dataEncode->addKeyData(objectKey: 'Results', data: $output);
	}

	/**
	 * Update token detail in Database for respective account
	 *
	 * @param array $userData Token Data
	 *
	 * @return void
	 */
	private function updateDb(&$userData): void
	{
		$this->http->req->clientDbObj = DbCommonFunction::connectClientDb(
			customerData: $this->http->req->s['customerData'],
			fetchFrom: 'Master'
		);

		$this->http->req->clientDbObj->execDbQuery(
			sql: "
				UPDATE
					`{$this->http->req->s['customerData']['userTable']}`
				SET
					`token` = :token
				WHERE
					id = :id",
			paramArr: [
				':token' => json_encode(value: $userData),
				':id' => $this->http->req->s['userData']['id']
			]
		);
	}

	/**
	 * Outputs active/newly generated session detail
	 *
	 * @return void
	 */
	private function startSession(): void
	{
		$httpRequestHash = $this->http->httpReqData['httpRequestHash'];

		$customerUserSessionIdKey = null;
		$customerUserSessionId = null;
	
		$authFound = false;
		$authFoundData = [];

		$customerUserConcurrencyKey = null;
		$customerUserConcurrencyData = null;

		$customerUserSessionIdKey = CacheServerKey::customerUserSessionId(
			customerId: $this->http->req->customerId,
			userId: $this->http->req->userId
		);

		if ($this->cacheExist(cacheKey: $customerUserSessionIdKey)) {
			$customerUserSessionId = $this->cacheGet(
				cacheKey: $customerUserSessionIdKey
			);
		}

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
			$customerUserConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);

			if ($this->cacheExist(cacheKey: $customerUserConcurrencyKey)) {
				if ($this->http->req->session === null) {
					$this->http->req->session = new Session();
					$this->http->req->session->initSessionHandler(sessionMode: Env::$sessionMode, options: []);
				}
				$customerUserConcurrencyData = json_decode(
					json: $this->cacheGet(
						cacheKey: $customerUserConcurrencyKey
					),
					associative: true
				);
				
				foreach ($customerUserConcurrencyData as $authId => $authData) {
					if (
						$authData['authMode'] === 'Token'
						&& !$this->cacheExist(cacheKey: CacheServerKey::token(token: $authId))
					) {
						unset($customerUserConcurrencyData[$authId]);
						continue;
					}
					if (
						$authData['authMode'] === 'Session'
					) {
						$time = Env::$timestamp - $authFoundData['authTimestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							$this->http->req->session->deleteSession(sessionId: $authId);
							unset($customerUserConcurrencyData[$authId]);
							continue;
						}
					}
					if (
						$customerUserSessionId !== null
						&& $customerUserSessionId === $authId
						&& $authData['httpRequestHash'] === $httpRequestHash
					) {
						$authFoundData = $authData;
						$authFound = true;
					}
				}
			}
		} else {
			if ($this->http->req->session === null) {
				$this->http->req->session = new Session();
				$this->http->req->session->initSessionHandler(sessionMode: Env::$sessionMode, options: []);
			}
			$this->http->req->session->sessionStartReadonly();
			if ($customerUserSessionId === session_id()) {
				if ($_SESSION['httpRequestHash'] === $httpRequestHash) {
					$authFoundData = $_SESSION;
					$authFound = true;
				}
			}
		}

		if (!$authFound) {
			$authFoundData = $this->generateSession();
			$authFound = true;

			$this->cacheSet(
				cacheKey: $customerUserSessionIdKey,
				cacheValue: $authFoundData['authId'],
				cacheExpire: Constant::$TOKEN_EXPIRY_TIME
			);

			$customerUserConcurrencyData[$authFoundData['authId']] = $authFoundData;
		}

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
			if (count($customerUserConcurrencyData) >= Env::$maxConcurrentLogin) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$customerUserConcurrencyKey = $customerUserConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);
			$this->cacheSet(
				cacheKey: $customerUserConcurrencyKey,
				cacheValue: json_encode($customerUserConcurrencyData),
				cacheExpire: Env::$concurrentAccessInterval
			);
		}
		$this->updateDb(userData: $authFoundData);

		$time = Env::$timestamp - $authFoundData['authTimestamp'];
		$output = [
			'SessionId' => $authFoundData['authId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', (Constant::$TOKEN_EXPIRY_TIME - $time))
		];

		$this->outputDetail(output: $output);
	}

	/**
	 * Global cache key exist
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	private function cacheExist($cacheKey) {
		return $this->http->req->clientCacheObj->cacheExist(cacheKey: $cacheKey);
	}

	/**
	 * Get global cache key
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	private function cacheGet($cacheKey) {
		return $this->http->req->clientCacheObj->cacheGet(cacheKey: $cacheKey);
	}

	/**
	 * Set global cache key
	 *
	 * @param string $cacheKey    Cache key
	 * @param string $cacheValue  Cache value
	 * @param int    $cacheExpire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	private function cacheSet($cacheKey, $cacheValue, $cacheExpire = 0) {
		return $this->http->req->clientCacheObj->cacheSet(
			cacheKey: $cacheKey,
			cacheValue: $cacheValue,
			cacheExpire: $cacheExpire
		);
	}

	/**
	 * Delete global cache key
	 *
	 * @param string $cacheKey Cache key
	 *
	 * @return mixed
	 */
	private function cacheDelete($cacheKey) {
		return $this->http->req->clientCacheObj->cacheDelete(cacheKey: $cacheKey);
	}
}
