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

use Microservices\App\CacheKey;
use Microservices\App\Http;
use Microservices\App\Constant;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\CommonFunction;
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
	 * DB Object
	 *
	 * @var null|object
	 */
	public $dbServerObj = null;

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
	 * Details pertaining to user
	 *
	 * @var array
	 */
	private $uDetails;

	/**
	 * Payload
	 *
	 * @var array
	 */
	private $payload = [];

	/**
	 * Http Object
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
		$this->http->req->loadCustomerDetails();

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
		if ($this->http->iConfig['server']['httpMethod'] !== Constant::$POST) {
			throw new \Exception(
				message: 'Invalid request method',
				code: HttpStatus::$NotFound
			);
		}

		$this->loadPayload();
		$this->loadUserDetails();
		$this->validateRequestIp();
		$this->validatePassword();

		if (Env::$enableRateLimitAtUsersPerIpLevel) {
			$rateLimiter = new RateLimiter();
			$result = $rateLimiter->check(
				prefix: Env::$rateLimitUsersPerIpPrefix,
				maxRequest: Env::$rateLimitUsersPerIpMaxUsers,
				secondsWindow: Env::$rateLimitUsersPerIpMaxUsersWindow,
				key: $this->http->iConfig['server']['httpRequestIP']
			);
			if ($result['allowed']) {
				// Process the request
			} else {
				// Return 429 Too Many Request
				throw new \Exception(
					message: $result['resetAt'] - Env::$timestamp,
					code: HttpStatus::$TooManyRequest
				);
			}
		}

		switch (Env::$authMode) {
			case 'Token':
				$this->outputTokenDetails();
				break;
			case 'Session':
				$this->startSession();
				break;
		}

		return true;
	}

	/**
	 * Function to load Payload
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function loadPayload(): void
	{
		// Check request method is POST
		if ($this->http->iConfig['server']['httpMethod'] !== Constant::$POST) {
			throw new \Exception(
				message: 'Invalid request method',
				code: HttpStatus::$NotFound
			);
		}

		$this->http->req->loadPayload();
		$this->payload = $this->http->req->dataDecode->get();

		// Check for necessary conditions variables
		foreach (['username', 'password'] as $value) {
			if (!isset($this->payload[$value]) || empty($this->payload[$value])) {
				throw new \Exception(
					message: 'Missing necessary parameters',
					code: HttpStatus::$NotFound
				);
			} else {
				$this->$value = $this->payload[$value];
			}
		}
	}

	/**
	 * Function to load user details from cache
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function loadUserDetails(): void
	{
		$cID = $this->http->req->s['cDetails']['id'];
		$customerUserKey = CacheKey::customerUser(
			cID: $cID,
			username: $this->payload['username']
		);
		// Redis - one can find the userID from customer username
		if (!$this->cacheExists(key: $customerUserKey)) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}
		$this->uDetails = json_decode(
			json: $this->getCache(
				key: $customerUserKey
			),
			associative: true
		);
		if (
			empty($this->uDetails['id'])
			|| empty($this->uDetails['id'])
		) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}
	}

	/**
	 * Function to validate source ip
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validateRequestIp(): void
	{
		$ipNumber = ip2long(ip: $this->http->iConfig['server']['httpRequestIP']);

		$cCidrKey = CacheKey::cCidr(
			cID: $this->http->req->s['cDetails']['id']
		);
		$gCidrKey = CacheKey::gCidr(
			gID: $this->uDetails['group_id']
		);
		$uCidrKey = CacheKey::uCidr(
			cID: $this->http->req->s['cDetails']['id'],
			uID: $this->uDetails['id']
		);
		$cidrChecked = false;
		foreach ([$cCidrKey, $gCidrKey, $uCidrKey] as $key) {
			if (!$cidrChecked) {
				$cidrChecked = CommonFunction::checkCacheCidr(
					IP: $this->http->iConfig['server']['httpRequestIP'],
					againstCacheKey: $key
				);
			}
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
		$rateLimiter = new RateLimiter();
		$result = $rateLimiter->check(
			prefix: Env::$rateLimitUserLoginPrefix,
			maxRequest: Env::$rateLimitMaxUserLoginRequest,
			secondsWindow: Env::$rateLimitMaxUserLoginRequestWindow,
			key: $this->http->iConfig['server']['httpRequestIP'] . $this->username
		);
		if ($result['allowed']) {
			// Process the request
		} else {
			// Return 429 Too Many Request
			throw new \Exception(
				message: $result['resetAt'] - Env::$timestamp,
				code: HttpStatus::$TooManyRequest
			);
		}
		// get hash from cache and compares with password
		if (
			!password_verify(
				password: $this->password,
				hash: $this->uDetails['password_hash']
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
				!$this->cacheExists(
					key: CacheKey::token(token: $token)
				)
			) {
				$this->setCache(
					key: CacheKey::token(token: $token),
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
	 * Outputs active/newly generated token details
	 *
	 * @return void
	 */
	private function outputTokenDetails(): void
	{
		$httpRequestHash = $this->http->iConfig['httpRequestHash'];

		$userTokenKey = CacheKey::userToken(
			uID: $this->uDetails['id']
		);

		$userTokenKeyExist = false;
		$userTokenKeyData = [];
		if ($this->cacheExists(key: $userTokenKey)) {
			$userTokenKeyExist = true;
			$userTokenKeyData = json_decode(
				json: $this->getCache(
					key: $userTokenKey
				),
				associative: true
			);
		}

		if (Env::$enableConcurrentLogins) {
			$userConcurrencyKey = CacheKey::userConcurrency(
				uID: $this->uDetails['id']
			);

			$userConcurrencyKeyExist = false;
			$userConcurrencyKeyData = '';
			if ($this->cacheExists(key: $userConcurrencyKey)) {
				$userConcurrencyKeyExist = true;
				$userConcurrencyKeyData = $this->getCache(
					key: $userConcurrencyKey
				);
			}
		}

		$tokenFound = false;
		$tokenFoundData = [];
		if ($userTokenKeyExist) {
			if (count($userTokenKeyData) > 0) {
				foreach ($userTokenKeyData as $token => $tData) {
					if ($this->cacheExists(key: CacheKey::token(token: $token))) {
						if (Env::$enableConcurrentLogins) {
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
							$this->deleteCache(
								key: CacheKey::token(
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
					Env::$enableConcurrentLogins
					&& count($userTokenKeyData) >= Env::$maxConcurrentLogins
				) {
					throw new \Exception(
						message: 'Account already in use. '
							. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
						code: HttpStatus::$Conflict
					);
				}
			} else {
				$this->deleteCache(key: $userTokenKey);
			}
		}

		if (!$tokenFound) {
			$newTokenData = $this->generateToken();
			$newTokenData['httpRequestHash'] = $httpRequestHash;

			unset($this->uDetails['password_hash']);
			foreach ($newTokenData as $k => $v) {
				$this->uDetails[$k] = $v;
			}

			$this->setCache(
				key: CacheKey::token(token: $newTokenData['token']),
				value: json_encode(
					value: $this->uDetails
				),
				expire: Constant::$TOKEN_EXPIRY_TIME
			);
			if (Env::$enableConcurrentLogins) {
				$userTokenKeyData[$newTokenData['token']] = $newTokenData;
			} else {
				$userTokenKeyData = [
					$newTokenData['token'] => $newTokenData
				];
			}
			$this->updateDB(userData: $userTokenKeyData);

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

		$this->setCache(
			key: $userTokenKey,
			value: json_encode(
				value: $userTokenKeyData
			),
			expire: Constant::$TOKEN_EXPIRY_TIME
		);
		if (Env::$enableConcurrentLogins) {
			$this->setCache(
				key: $userConcurrencyKey,
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
		$this->http->res->dataEncode->addKeyData(key: 'Results', data: $output);
	}

	/**
	 * Update token details in DB for respective account
	 *
	 * @param array $userData Token Data
	 *
	 * @return void
	 */
	private function updateDB(&$userData): void
	{
		DbCommonFunction::setDbConnection($this->http->req, fetchFrom: 'Master');
		$this->dbServerObj = &DbCommonFunction::$masterDb[$this->http->req->cId];

		$usersTable = $this->http->req->usersTable;
		$this->dbServerObj->execDbQuery(
			sql: "
				UPDATE
					`{$usersTable}`
				SET
					`token` = :token
				WHERE
					id = :id",
			params: [
				':token' => json_encode($userData),
				':id' => $this->uDetails['id']
			]
		);
	}

	/**
	 * Outputs active/newly generated session details
	 *
	 * @return void
	 */
	private function startSession(): void
	{
		$httpRequestHash = $this->http->iConfig['httpRequestHash'];

		$userSessionKey = CacheKey::userSessionId(
			uID: $this->uDetails['id']
		);

		$userSessionKeyExist = false;
		$userSessionKeyData = [];
		if ($this->cacheExists(key: $userSessionKey)) {
			$userSessionKeyExist = true;
			$userSessionKeyData = json_decode(
				json: $this->getCache(
					key: $userSessionKey
				),
				associative: true
			);
		}

		if (Env::$enableConcurrentLogins) {
			$userConcurrencyKey = CacheKey::userConcurrency(
				uID: $this->uDetails['id']
			);

			$userConcurrencyKeyExist = false;
			$userConcurrencyKeyData = '';
			if ($this->cacheExists(key: $userConcurrencyKey)) {
				$userConcurrencyKeyExist = true;
				$userConcurrencyKeyData = $this->getCache(
					key: $userConcurrencyKey
				);
			}
		}

		$sessionFound = false;
		$sessionFoundData = [];
		if ($userSessionKeyExist) {
			if (count($userSessionKeyData) > 0) {
				foreach ($userSessionKeyData as $sessionId => $tData) {
					if (Env::$enableConcurrentLogins) {
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
				if (Env::$enableConcurrentLogins) {
					if (count($userSessionKeyData) >= Env::$maxConcurrentLogins) {
						throw new \Exception(
							message: 'Account already in use. '
								. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
							code: HttpStatus::$Conflict
						);
					}
				}
			} else {
				$this->deleteCache(key: $userSessionKey);
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

			unset($this->uDetails['password_hash']);
			foreach ($newSessionData as $k => $v) {
				$this->uDetails[$k] = $v;
			}

			$_SESSION = $this->uDetails;

			if (Env::$enableConcurrentLogins) {
				$userSessionKeyData[$newSessionData['sessionId']] = $newSessionData;
			} else {
				$userSessionKeyData = [
					$newSessionData['sessionId'] => $newSessionData
				];
			}
			$this->updateDB(userData: $userSessionKeyData);

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

		$this->setCache(
			key: $userSessionKey,
			value: json_encode(
				value: $userSessionKeyData
			),
			expire: Constant::$TOKEN_EXPIRY_TIME
		);
		if (Env::$enableConcurrentLogins) {
			$this->setCache(
				key: $userConcurrencyKey,
				value: $sessionId,
				expire: Env::$concurrentAccessInterval
			);
		}
		$time = Env::$timestamp - $sessionFoundData['sessionExpiryTimestamp'];
		$output = [
			'SessionId' => $sessionFoundData['sessionId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', (Constant::$TOKEN_EXPIRY_TIME - $time))
		];

		$this->http->initResponse();
		$this->http->res->dataEncode->startObject();
		$this->http->res->dataEncode->addKeyData(key: 'Results', data: $output);
	}

	/**
	 * Checks if cache key exist
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	private function cacheExists($key) {
		return DbCommonFunction::$gCacheServer->cacheExists(key: $key);
	}

	/**
	 * Get cache on basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	private function getCache($key) {
		return DbCommonFunction::$gCacheServer->getCache(key: $key);
	}

	/**
	 * Set cache on basis of key
	 *
	 * @param string $key    Cache key
	 * @param string $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	private function setCache($key, $value, $expire = 0) {
		return DbCommonFunction::$gCacheServer->setCache(
			key: $key,
			value: $value,
			expire: $expire
		);
	}

	/**
	 * Delete basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	private function deleteCache($key) {
		return DbCommonFunction::$gCacheServer->deleteCache(key: $key);
	}
}
