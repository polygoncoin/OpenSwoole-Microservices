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
use Microservices\App\RateLimiter;
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
		$this->http->req->loadCustomerDetail();

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
		if ($this->http->httpReqDetailArr['server']['httpMethod'] !== Constant::$POST) {
			throw new \Exception(
				message: 'Invalid request method',
				code: HttpStatus::$NotFound
			);
		}

		$this->loadPayload();
		$this->loadUserDetail();
		$this->validateRequestIp();
		$this->validatePassword();

		if (Env::$enableRateLimitForUserPerIp) {
			$rateLimiter = new RateLimiter($this->http);
			$result = $rateLimiter->check(
				prefix: Env::$rateLimitUserPerIpPrefix,
				maxRequest: Env::$rateLimitMaxUserPerIp,
				secondsWindow: Env::$rateLimitMaxUserPerIpWindow,
				rateLimitKey: $this->http->httpReqDetailArr['server']['httpRequestIP']
			);
			if ($result['allowed']) {
				// Process the request
			} else {
				// Return 429 Too Many request
				throw new \Exception(
					message: $result['resetOn'] - Env::$timestamp,
					code: HttpStatus::$TooManyRequest
				);
			}
		}

		switch (Env::$authMode) {
			case 'Token':
				$this->outputTokenDetail();
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
		if ($this->http->httpReqDetailArr['server']['httpMethod'] !== Constant::$POST) {
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
	 * Load user detail from cache
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function loadUserDetail(): void
	{
		$this->http->req->clientCacheObj = DbCommonFunction::connectClientCache($this->http->req, fetchFrom: 'Master');

		$cID = $this->http->req->cID;
		$customerUserKey = CacheServerKey::customerUsername(
			cID: $cID,
			username: $this->payload['username']
		);
		// Redis - one can find the userID from customer username
		if (!$this->cacheExist(cacheKey: $customerUserKey)) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}
		$uDetail = json_decode(
			json: $this->cacheGet(
				cacheKey: $customerUserKey
			),
			associative: true
		);
		if (
			empty($uDetail['id'])
			|| empty($uDetail['id'])
		) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}
		$this->http->req->s['uDetail'] = $uDetail;
		$this->http->req->uID = $uDetail['id'];
		$this->http->req->gID = $uDetail['group_id'];
	}

	/**
	 * Validate source ip
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validateRequestIp(): void
	{
		$ipNumber = ip2long(ip: $this->http->httpReqDetailArr['server']['httpRequestIP']);

		CommonFunction::checkCacheCidr(
			cacheObj: DbCommonFunction::$gCacheServer,
			IP: $this->http->httpReqDetailArr['server']['httpRequestIP'],
			cidrCacheKey: CacheServerKey::customerCidr(
				cID: $this->http->req->cID
			)
		);

		if ($this->http !== null) {
			CommonFunction::checkCacheCidr(
				cacheObj: $this->http->req->clientCacheObj,
				IP: $this->http->httpReqDetailArr['server']['httpRequestIP'],
				cidrCacheKey: CacheServerKey::customerGroupCidr(
					cID: $this->http->req->cID,
					gID: $this->http->req->gID
				)
			);

			CommonFunction::checkCacheCidr(
				cacheObj: $this->http->req->clientCacheObj,
				IP: $this->http->httpReqDetailArr['server']['httpRequestIP'],
				cidrCacheKey: CacheServerKey::customerUserCidr(
					cID: $this->http->req->cID,
					uID: $this->http->req->uID
				)
			);
		}
	}

	/**
	 * Validates password from its hash present in cache
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validatePassword(): void
	{
		$rateLimiter = new RateLimiter($this->http);
		$result = $rateLimiter->check(
			prefix: Env::$rateLimitUserLoginPrefix,
			maxRequest: Env::$rateLimitMaxUserLoginRequest,
			secondsWindow: Env::$rateLimitMaxUserLoginRequestWindow,
			rateLimitKey: $this->http->httpReqDetailArr['server']['httpRequestIP'] . $this->username
		);
		if ($result['allowed']) {
			// Process the request
		} else {
			// Return 429 Too Many request
			throw new \Exception(
				message: $result['resetOn'] - Env::$timestamp,
				code: HttpStatus::$TooManyRequest
			);
		}
		// get hash from cache and compares with password
		if (
			!password_verify(
				password: $this->password,
				hash: $this->http->req->s['uDetail']['password_hash']
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
					value: '{}',
					expire: Constant::$TOKEN_EXPIRY_TIME
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
	private function outputTokenDetail(): void
	{
		$httpRequestHash = $this->http->httpReqDetailArr['httpRequestHash'];

		if (Env::$enableConcurrentLogin) {
			$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
				cID: $this->http->req->cID,
				uID: $this->http->req->uID
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
			cID: $this->http->req->cID,
			uID: $this->http->req->uID
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

			unset($this->http->req->s['uDetail']['password_hash']);
			foreach ($newTokenData as $k => $v) {
				$this->http->req->s['uDetail'][$k] = $v;
			}

			$this->cacheSet(
				cacheKey: CacheServerKey::token(token: $newTokenData['token']),
				value: json_encode(
					value: $this->http->req->s['uDetail']
				),
				expire: Constant::$TOKEN_EXPIRY_TIME
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
			value: json_encode(
				value: $userTokenKeyData
			),
			expire: Constant::$TOKEN_EXPIRY_TIME
		);
		if (Env::$enableConcurrentLogin) {
			$this->cacheSet(
				cacheKey: $userConcurrencyKey,
				value: $token,
				expire: Env::$concurrentAccessInterval
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
	 * Update token detail in DB for respective account
	 *
	 * @param array $userData Token Data
	 *
	 * @return void
	 */
	private function updateDb(&$userData): void
	{
		$this->http->req->clientDbObj = DbCommonFunction::connectClientDb($this->http->req, fetchFrom: 'Master');

		$this->http->req->clientDbObj->execDbQuery(
			sql: "
				UPDATE
					`{$this->http->req->s['cDetail']['usersTable']}`
				SET
					`token` = :token
				WHERE
					id = :id",
			paramArr: [
				':token' => json_encode($userData),
				':id' => $this->http->req->s['uDetail']['id']
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
		$httpRequestHash = $this->http->httpReqDetailArr['httpRequestHash'];

		if (Env::$enableConcurrentLogin) {
			$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
				cID: $this->http->req->cID,
				uID: $this->http->req->uID
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
			cID: $this->http->req->cID,
			uID: $this->http->req->uID
		);

		if ($this->cacheExist(cacheKey: $userSessionKey)) {
			$userSessionKeyData = json_decode(
				json: $this->cacheGet(
					cacheKey: $userSessionKey
				),
				associative: true
			);
			if (count($userSessionKeyData) > 0) {
				foreach ($userSessionKeyData as $sessionID => $tData) {
					if (Env::$enableConcurrentLogin) {
						if (
							$tData['httpRequestHash'] === $httpRequestHash
							&& $userConcurrencyKeyExist
							&& $userConcurrencyKeyData === $sessionID
							&& $sessionID === session_id()
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
							&& $sessionID === session_id()
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
							Session::deleteSession(sessionID: $sessionID);
							unset($userSessionKeyData[$sessionID]);
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
				'sessionID' => session_id(),
				'timestamp' => Env::$timestamp,
				'httpRequestHash' => $httpRequestHash,
				'sessionExpiryTimestamp' => (Env::$timestamp + Constant::$TOKEN_EXPIRY_TIME)
			];

			unset($this->http->req->s['uDetail']['password_hash']);
			foreach ($newSessionData as $k => $v) {
				$this->http->req->s['uDetail'][$k] = $v;
			}

			$_SESSION = $this->http->req->s['uDetail'];

			if (Env::$enableConcurrentLogin) {
				$userSessionKeyData[$newSessionData['sessionID']] = $newSessionData;
			} else {
				$userSessionKeyData = [
					$newSessionData['sessionID'] => $newSessionData
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

		$sessionID = $sessionFoundData['sessionID'];

		$this->cacheSet(
			cacheKey: $userSessionKey,
			value: json_encode(
				value: $userSessionKeyData
			),
			expire: Constant::$TOKEN_EXPIRY_TIME
		);
		if (Env::$enableConcurrentLogin) {
			$this->cacheSet(
				cacheKey: $userConcurrencyKey,
				value: $sessionID,
				expire: Env::$concurrentAccessInterval
			);
		}
		$time = Env::$timestamp - $sessionFoundData['sessionExpiryTimestamp'];
		$output = [
			'sessionID' => $sessionFoundData['sessionID'],
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
	 * @param string $cacheKey Cache key
	 * @param string $value    Cache value
	 * @param int    $expire   Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	private function cacheSet($cacheKey, $value, $expire = 0) {
		return $this->http->req->clientCacheObj->cacheSet(
			cacheKey: $cacheKey,
			value: $value,
			expire: $expire
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
