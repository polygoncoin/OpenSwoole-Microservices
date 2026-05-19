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

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableRateLimitForUserPerIp')) {
			$this->http->req->rateLimiter->checkRateLimit(
				rateLimitPrefix: Env::$rateLimitUserPerIpPrefix,
				rateLimitMaxRequest: $this->http->req->s['customerData']['rateLimitMaxUserPerIp'],
				rateLimitMaxRequestWindow: $this->http->req->s['customerData']['rateLimitMaxUserPerIpWindow'],
				rateLimitKey: $this->http->httpReqData['server']['httpRequestIP']
			);
		}

		switch (Env::$authMode) {
			case 'Token':
				$this->outputTokenData();
				break;
			case 'Session':
				$this->startSession();
				break;
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
		$this->http->req->rateLimiter->checkRateLimit(
			rateLimitPrefix: Env::$rateLimitUserLoginPrefix,
			rateLimitMaxRequest: $this->http->req->s['customerData']['rateLimitMaxUserLoginRequest'],
			rateLimitMaxRequestWindow: $this->http->req->s['customerData']['rateLimitMaxUserLoginRequestWindow'],
			rateLimitKey: $this->http->httpReqData['server']['httpRequestIP'] . ':' . $this->username
		);
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
			$token = bin2hex(string: random_bytes(length: 32));

			if (
				!$this->cacheExist(
					cacheKey: CacheServerKey::token(token: $token)
				)
			) {
				$this->cacheSet(
					cacheKey: CacheServerKey::token(token: $token),
					cacheValue: '{}',
					cacheExpire: Constant::$TOKEN_EXPIRY_TIME
				);
				$userTokenKeyData = [
					'token' => $token,
					'timestamp' => Env::$timestamp
				];
				break;
			}
		}
		return $userTokenKeyData;
	}

	/**
	 * Outputs active/newly generated token detail
	 *
	 * @return void
	 */
	private function outputTokenData(): void
	{
		$httpRequestHash = $this->http->httpReqData['httpRequestHash'];

		$userTokenKey = null;
		$userTokenKeyExist = false;
		$userTokenKeyData = [];

		$tokenFound = false;
		$tokenFoundData = [];

		$userConcurrencyKey = null;
		$userConcurrencyKeyData = null;

		$userTokenKey = CacheServerKey::customerUserToken(
			customerId: $this->http->req->customerId,
			userId: $this->http->req->userId
		);

		if ($this->cacheExist(cacheKey: $userTokenKey)) {
			$userTokenKeyExist = true;
			$userTokenKeyData = json_decode(
				json: $this->cacheGet(
					cacheKey: $userTokenKey
				),
				associative: true
			);
		}

		if (
			$userTokenKeyExist
			&& count($userTokenKeyData) === 0
		) {
			$this->cacheDelete(cacheKey: $userTokenKey);
		} else {
			if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
				$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
					customerId: $this->http->req->customerId,
					userId: $this->http->req->userId
				);

				if ($this->cacheExist(cacheKey: $userConcurrencyKey)) {
					$userConcurrencyKeyData = $this->cacheGet(
						cacheKey: $userConcurrencyKey
					);

					foreach ($userTokenKeyData as $token => $tokenData) {
						if (!$this->cacheExist(cacheKey: CacheServerKey::token(token: $token))) {
							unset($userTokenKeyData[$token]);
							continue;
						}
						$timeLeft = Env::$timestamp - $tokenData['timestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							$this->cacheDelete(
								cacheKey: CacheServerKey::token(
									token: $token
								)
							);
							unset($userTokenKeyData[$token]);
							continue;
						}
						if (
							$tokenData['httpRequestHash'] === $httpRequestHash
							&& (Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0
							&& $userConcurrencyKeyData === $token
						) {
							$tokenFoundData = $tokenData;
							$tokenFound = true;
						}
					}
				}
			} else {
				$token = key($userTokenKeyData);
				$tokenData = $userTokenKeyData[$token];
				if (!$this->cacheExist(cacheKey: CacheServerKey::token(token: $token))) {
					unset($userTokenKeyData[$token]);
				}
				$timeLeft = Env::$timestamp - $tokenData['timestamp'];
				if (
					isset($userTokenKeyData[$token])
					&& (Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0
				) {
					$this->cacheDelete(
						cacheKey: CacheServerKey::token(
							token: $token
						)
					);
					unset($userTokenKeyData[$token]);
				}
				if (
					isset($userTokenKeyData[$token])
					&& (Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0
					&& $tokenData['httpRequestHash'] === $httpRequestHash
				) {
					$tokenFoundData = $tokenData;
					$tokenFound = true;
				} else {
					$userTokenKeyData = [];
				}
			}
		}

		if (!$tokenFound) {
			$newTokenData = $this->generateToken();
			$newTokenData['httpRequestHash'] = $httpRequestHash;

			unset($this->http->req->s['userData']['password_hash']);
			foreach ($newTokenData as $k => $v) {
				$this->http->req->s['userData'][$k] = $v;
			}

			$this->cacheSet(
				cacheKey: CacheServerKey::token(token: $newTokenData['token']),
				cacheValue: json_encode(
					value: $this->http->req->s['userData']
				),
				cacheExpire: Constant::$TOKEN_EXPIRY_TIME
			);

			$userTokenKeyData[$newTokenData['token']] = $newTokenData;
			$tokenFoundData = &$newTokenData;
			$tokenFound = true;
		}

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
			if (count($userTokenKeyData) >= Env::$maxConcurrentLogin) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$userConcurrencyKey = $userConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);
			$this->cacheSet(
				cacheKey: $userConcurrencyKey,
				cacheValue: $tokenFoundData['token'],
				cacheExpire: Env::$concurrentAccessInterval
			);
		}
		$this->cacheSet(
			cacheKey: $userTokenKey,
			cacheValue: json_encode(
				value: $userTokenKeyData
			),
			cacheExpire: Constant::$TOKEN_EXPIRY_TIME
		);
		$this->updateDb(userData: $userTokenKeyData);

		$time = Env::$timestamp - $tokenFoundData['timestamp'];
		$output = [
			'Token' => $tokenFoundData['token'],
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

		$currentSessionId = session_id();

		$userSessionKey = null;
		$userSessionKeyExist = false;
		$userSessionKeyData = [];

		$sessionFound = false;
		$sessionFoundData = [];

		$userConcurrencyKey = null;
		$userConcurrencyKeyData = null;

		$userSessionKey = CacheServerKey::customerUserSessionId(
			customerId: $this->http->req->customerId,
			userId: $this->http->req->userId
		);

		if ($this->cacheExist(cacheKey: $userSessionKey)) {
			$userSessionKeyExist = true;
			$userSessionKeyData = json_decode(
				json: $this->cacheGet(
					cacheKey: $userSessionKey
				),
				associative: true
			);
		}

		if (
			$userSessionKeyExist
			&& count($userSessionKeyData) === 0
		) {
			$this->cacheDelete(cacheKey: $userSessionKey);
		} else {
			if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
				$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
					customerId: $this->http->req->customerId,
					userId: $this->http->req->userId
				);

				if ($this->cacheExist(cacheKey: $userConcurrencyKey)) {
					$userConcurrencyKeyData = $this->cacheGet(
						cacheKey: $userConcurrencyKey
					);

					foreach ($userSessionKeyData as $sessionId => $sessionData) {
						$timeLeft = Env::$timestamp - $sessionData['sessionExpiryTimestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							Session::deleteSession(sessionId: $sessionId);
							unset($userSessionKeyData[$sessionId]);
							continue;
						}
						if (
							$sessionId === $currentSessionId
							&& $userConcurrencyKeyData === $sessionId
							&& (Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0
							&& $sessionData['httpRequestHash'] === $httpRequestHash
						) {
							$sessionFoundData = $sessionData;
							$sessionFound = true;
						}
					}
				}
			} else {
				$sessionId = key($userSessionKeyData);
				$sessionData = $userSessionKeyData[$sessionId];
				$timeLeft = Env::$timestamp - $sessionData['sessionExpiryTimestamp'];

				if (
					$sessionId === $currentSessionId
					&& (Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0
					&& $sessionData['httpRequestHash'] === $httpRequestHash
				) {
					$sessionFoundData = $sessionData;
					$sessionFound = true;
				} else {
					$userSessionKeyData = [];
				}
			}
		}

		if (!$sessionFound) {
			Session::sessionStartReadWrite();
			$newSessionData = [
				'sessionId' => session_id(),
				'timestamp' => Env::$timestamp,
				'httpRequestHash' => $httpRequestHash,
				'sessionExpiryTimestamp' => (Env::$timestamp + Constant::$TOKEN_EXPIRY_TIME)
			];

			unset($this->http->req->s['userData']['password_hash']);
			foreach ($newSessionData as $k => $v) {
				$this->http->req->s['userData'][$k] = $v;
			}
			$_SESSION = $this->http->req->s['userData'];

			$userSessionKeyData[$newSessionData['sessionId']] = $newSessionData;
			$sessionFoundData = &$newSessionData;
			$sessionFound = true;
		}

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
			if (count($userSessionKeyData) >= Env::$maxConcurrentLogin) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$userConcurrencyKey = $userConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);
			$this->cacheSet(
				cacheKey: $userConcurrencyKey,
				cacheValue: $sessionFoundData['sessionId'],
				cacheExpire: Env::$concurrentAccessInterval
			);
		}
		$this->cacheSet(
			cacheKey: $userSessionKey,
			cacheValue: json_encode(
				value: $userSessionKeyData
			),
			cacheExpire: Constant::$TOKEN_EXPIRY_TIME
		);
		$this->updateDb(userData: $userSessionKeyData);

		$time = Env::$timestamp - $sessionFoundData['sessionExpiryTimestamp'];
		$output = [
			'sessionId' => $sessionFoundData['sessionId'],
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
