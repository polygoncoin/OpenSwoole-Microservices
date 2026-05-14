<?php

/**
 * Login
 * php version 8.3
 *
 * @category  Login
 * @package   Openswoole_Microservices
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
 * @package   Openswoole_Microservices
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
		$this->http->req->loadCustomerData();

		return true;
	}

	/**
	 * Process
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function process(): bool
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
		CommonFunction::checkClosedWebRequestCidr(http: $this->http);
		$this->validatePassword();

		if (Env::$enableRateLimitForUserPerIp) {
			$this->http->req->rateLimiter->checkRateLimit(
				rateLimitPrefix: Env::$rateLimitUserPerIpPrefix,
				rateLimitMaxRequest: Env::$rateLimitMaxUserPerIp,
				rateLimitMaxRequestWindow: Env::$rateLimitMaxUserPerIpWindow,
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
			rateLimitMaxRequest: Env::$rateLimitMaxUserLoginRequest,
			rateLimitMaxRequestWindow: Env::$rateLimitMaxUserLoginRequestWindow,
			rateLimitKey: $this->http->httpReqData['server']['httpRequestIP'] . $this->username
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

		if (Env::$enableConcurrentLogin) {
			$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);

			$userConcurrencyKeyExist = false;
			$userConcurrencyKeyData = '';
			if ($this->cacheExist(cacheKey: $userConcurrencyKey)) {
				$userConcurrencyKeyExist = true;
				$userConcurrencyKeyData = $this->cacheGet(
					cacheKey: $userConcurrencyKey
				);
			}
		}

		$tokenFound = false;
		$tokenFoundData = [];
		$userTokenKeyData = [];

		$userTokenKey = CacheServerKey::customerUserToken(
			customerId: $this->http->req->customerId,
			userId: $this->http->req->userId
		);

		if ($this->cacheExist(cacheKey: $userTokenKey)) {
			$userTokenKeyData = json_decode(
				json: $this->cacheGet(
					cacheKey: $userTokenKey
				),
				associative: true
			);
			if (count($userTokenKeyData) > 0) {
				foreach ($userTokenKeyData as $token => $tData) {
					if ($this->cacheExist(cacheKey: CacheServerKey::token(token: $token))) {
						if (Env::$enableConcurrentLogin) {
							if (
								$tData['httpRequestHash'] === $httpRequestHash
								&& $userConcurrencyKeyExist
								&& $userConcurrencyKeyData === $token
							) {
								$timeLeft = Env::$timestamp - $tData['timestamp'];
								if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
									$tokenFoundData = $tData;
									$tokenFound = true;
									continue;
								}
							}
						} else {
							if (
								$tData['httpRequestHash'] === $httpRequestHash
								&& $userConcurrencyKeyData === $token
							) {
								$timeLeft = Env::$timestamp - $tData['timestamp'];
								if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
									$tokenFoundData = $tData;
									$tokenFound = true;
									continue;
								}
							}
						}
						$timeLeft = Env::$timestamp - $tData['timestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							$this->cacheDelete(
								cacheKey: CacheServerKey::token(
									token: $token
								)
							);
							unset($userTokenKeyData[$token]);
						}
					} else {
						unset($userTokenKeyData[$token]);
					}
				}
				if (
					Env::$enableConcurrentLogin
					&& count($userTokenKeyData) >= Env::$maxConcurrentLogin
				) {
					throw new \Exception(
						message: 'Account already in use. '
							. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
						code: HttpStatus::$Conflict
					);
				}
			} else {
				$this->cacheDelete(cacheKey: $userTokenKey);
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
			if (Env::$enableConcurrentLogin) {
				$userTokenKeyData[$newTokenData['token']] = $newTokenData;
			} else {
				$userTokenKeyData = [
					$newTokenData['token'] => $newTokenData
				];
			}
			$this->updateDb(userData: $userTokenKeyData);

			$tokenFoundData = &$newTokenData;
			$tokenFound = true;
		}

		if (!$tokenFound) {
			throw new \Exception(
				message: 'Unexpected error occured during login',
				code: HttpStatus::$InternalServerError
			);
		}

		$token = $tokenFoundData['token'];

		$this->cacheSet(
			cacheKey: $userTokenKey,
			cacheValue: json_encode(
				value: $userTokenKeyData
			),
			cacheExpire: Constant::$TOKEN_EXPIRY_TIME
		);
		if (Env::$enableConcurrentLogin) {
			$this->cacheSet(
				cacheKey: $userConcurrencyKey,
				cacheValue: $token,
				cacheExpire: Env::$concurrentAccessInterval
			);
		}
		$time = Env::$timestamp - $tokenFoundData['timestamp'];
		$output = [
			'Token' => $tokenFoundData['token'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', (Constant::$TOKEN_EXPIRY_TIME - $time))
		];

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
					`{$this->http->req->s['customerData']['usersTable']}`
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

		if (Env::$enableConcurrentLogin) {
			$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);

			$userConcurrencyKeyExist = false;
			$userConcurrencyKeyData = '';
			if ($this->cacheExist(cacheKey: $userConcurrencyKey)) {
				$userConcurrencyKeyExist = true;
				$userConcurrencyKeyData = $this->cacheGet(
					cacheKey: $userConcurrencyKey
				);
			}
		}

		$sessionFound = false;
		$sessionFoundData = [];
		$userSessionKeyData = [];

		$userSessionKey = CacheServerKey::customerUserSessionId(
			customerId: $this->http->req->customerId,
			userId: $this->http->req->userId
		);

		if ($this->cacheExist(cacheKey: $userSessionKey)) {
			$userSessionKeyData = json_decode(
				json: $this->cacheGet(
					cacheKey: $userSessionKey
				),
				associative: true
			);
			if (count($userSessionKeyData) > 0) {
				foreach ($userSessionKeyData as $sessionId => $tData) {
					if (Env::$enableConcurrentLogin) {
						if (
							$tData['httpRequestHash'] === $httpRequestHash
							&& $userConcurrencyKeyExist
							&& $userConcurrencyKeyData === $sessionId
							&& $sessionId === session_id()
						) {
							$timeLeft = Env::$timestamp - $tData['sessionExpiryTimestamp'];
							if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
								$sessionFoundData = $tData;
								$sessionFound = true;
								continue;
							}
						}
					} else {
						if (
							$tData['httpRequestHash'] === $httpRequestHash
							&& $sessionId === session_id()
						) {
							$timeLeft = Env::$timestamp - $tData['sessionExpiryTimestamp'];
							if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
								$sessionFoundData = $tData;
								$sessionFound = true;
								continue;
							}
						}
					}
					if (isset($tData['sessionExpiryTimestamp'])) {
						$timeLeft = Env::$timestamp - $tData['sessionExpiryTimestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							Session::deleteSession(sessionId: $sessionId);
							unset($userSessionKeyData[$sessionId]);
						}
					}
				}
				if (Env::$enableConcurrentLogin) {
					if (count($userSessionKeyData) >= Env::$maxConcurrentLogin) {
						throw new \Exception(
							message: 'Account already in use. '
								. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
							code: HttpStatus::$Conflict
						);
					}
				}
			} else {
				$this->cacheDelete(cacheKey: $userSessionKey);
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

			if (Env::$enableConcurrentLogin) {
				$userSessionKeyData[$newSessionData['sessionId']] = $newSessionData;
			} else {
				$userSessionKeyData = [
					$newSessionData['sessionId'] => $newSessionData
				];
			}
			$this->updateDb(userData: $userSessionKeyData);

			$sessionFoundData = &$newSessionData;
			$sessionFound = true;
		}

		if (!$sessionFound) {
			throw new \Exception(
				message: 'Unexpected error occured during login',
				code: HttpStatus::$InternalServerError
			);
		}

		$sessionId = $sessionFoundData['sessionId'];

		$this->cacheSet(
			cacheKey: $userSessionKey,
			cacheValue: json_encode(
				value: $userSessionKeyData
			),
			cacheExpire: Constant::$TOKEN_EXPIRY_TIME
		);
		if (Env::$enableConcurrentLogin) {
			$this->cacheSet(
				cacheKey: $userConcurrencyKey,
				cacheValue: $sessionId,
				cacheExpire: Env::$concurrentAccessInterval
			);
		}
		$time = Env::$timestamp - $sessionFoundData['sessionExpiryTimestamp'];
		$output = [
			'sessionId' => $sessionFoundData['sessionId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', (Constant::$TOKEN_EXPIRY_TIME - $time))
		];

		$this->http->initResponse();
		$this->http->res->dataEncode->startObject();
		$this->http->res->dataEncode->addKeyData(objectKey: 'Results', data: $output);
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
