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
	public $customer_user_username = null;

	/**
	 * Password for login
	 * 
	 * @var null|string
	 */
	public $customer_user_password = null;

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
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
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
		if ($this->httpObject->httpReqData['server']['httpRequestMethod'] !== Constant::$POST) {
			throw new \Exception(
				message: 'Invalid request method',
				code: HttpStatus::$NotFound
			);
		}

		$this->loadPayload();
		$this->loadUserData();
		CommonFunction::checkPrivateRequestCidr(
			httpObject: $this->httpObject
		);
		$this->validatePassword();

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_rate_limiting_for_user_per_ip'
			)
			&& !empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_per_ip'])
			&& !empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_per_ip_window'])
		) {
			$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
				rateLimitPrefix: Env::$rateLimitUserAsPerHttpRequestIpPrefix,
				rateLimitMaxRequest: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_per_ip'],
				rateLimitMaxRequestWindow: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_per_ip_window'],
				rateLimitKey: $this->httpObject->httpReqData['server']['httpRequestIp']
			);
		}

		if ($this->httpObject->httpRequestObject->isPrivateSessionDomain) {
			$this->startSession();
		} elseif ($this->httpObject->httpRequestObject->isPrivateTokenDomain) {
			$this->outputTokenData();
		} else {
			throw new \Exception(
				message: "Invalid domain: '{$this->httpObject->httpReqData['server']['domainName']}' to login",
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
		if ($this->httpObject->httpReqData['server']['httpRequestMethod'] !== Constant::$POST) {
			throw new \Exception(
				message: 'Invalid HTTP request method: ' . $this->httpObject->httpReqData['server']['httpRequestMethod'],
				code: HttpStatus::$NotFound
			);
		}

		$this->httpObject->httpRequestObject->loadPayload();
		$this->payload = $this->httpObject->httpRequestObject->dataDecodeObject->get();

		// Check for required conditions variables
		$requiredParamData = [
			'username' => 'customer_user_username',
			'password' => 'customer_user_password'
		];

		foreach ($requiredParamData as $param => $value) {
			if (
				!isset($this->payload[$param])
				|| empty($this->payload[$param])
			) {
				throw new \Exception(
					message: 'Missing required parameters',
					code: HttpStatus::$NotFound
				);
			} else {
				$this->$value = $this->payload[$param];
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
		$customerId = $this->httpObject->httpRequestObject->customerId;
		$customerUserKey = CacheServerKey::customerUsername(
			customerId: $customerId,
			username: $this->payload['username']
		);
		// Redis - one can find the customerUserId from customer username
		if (
			!$this->cacheExist(
				cacheKey: $customerUserKey
			)
		) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}

		$userData = $this->cacheGet(
			cacheKey: $customerUserKey
		);
		if (
			empty($userData['customer_user_id'])
			|| empty($userData['customer_user_id'])
		) {
			throw new \Exception(
				message: 'Invalid credentials',
				code: HttpStatus::$Unauthorized
			);
		}
		$this->httpObject->httpRequestObject->activeRequestData['userData'] = $userData;
		$this->httpObject->httpRequestObject->customerUserId = $userData['customer_user_id'];
		$this->httpObject->httpRequestObject->customerUserGroupId = $userData['customer_user_group_id'];
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
			!empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_login_request'])
			&& !empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_login_request_window'])
		) {
			$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
				rateLimitPrefix: Env::$rateLimitUserLoginPrefix,
				rateLimitMaxRequest: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_login_request'],
				rateLimitMaxRequestWindow: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_rate_limit_max_user_login_request_window'],
				rateLimitKey: $this->httpObject->httpReqData['server']['httpRequestIp'] . ':' . $this->customer_user_username
			);
		}

		// get hash from cache and compares with password
		if (
			!password_verify(
				password: $this->customer_user_password,
				hash: $this->httpObject->httpRequestObject->activeRequestData['userData']['customer_user_password_hash']
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
			$authId = bin2hex(
				string: random_bytes(
					length: 32
				)
			);

			if (
				!$this->cacheExist(
					cacheKey: CacheServerKey::token(
						token: $authId
					)
				)
			) {
				$this->cacheSet(
					cacheKey: CacheServerKey::token(
						token: $authId
					),
					cacheValue: '{}',
					cacheExpire: Constant::$TOKEN_EXPIRY_TIME
				);
				$userTokenData = [
					'authId' => $authId,
					'authMode' => 'Token',
					'authTimestamp' => Env::$timestamp,
					'httpRequestHash' => $this->httpObject->httpReqData['httpRequestHash']
				];
				break;
			}
		}

		foreach ($this->httpObject->httpRequestObject->activeRequestData['userData'] as $userDataKey => &$userDataKeyValue) {
			$userTokenData[$userDataKey] = $userDataKeyValue;
		}

		$this->cacheSet(
			cacheKey: CacheServerKey::token(
				token: $userTokenData['authId']
			),
			cacheValue: $userTokenData,
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
		if ($this->httpObject->httpRequestObject->sessionObject === Constant::$NULL) {
			$this->httpObject->httpRequestObject->sessionObject = new Session();
			$this->httpObject->httpRequestObject->sessionObject->sessionDomain = $this->httpObject->httpReqData['server']['domainName'];
			$this->httpObject->httpRequestObject->sessionObject->initSessionHandler(
				customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
				options: []
			);
		}
		$this->httpObject->httpRequestObject->sessionObject->sessionStartReadWrite();
		$userSessionData = [
			'authId' => session_id(),
			'authMode' => 'Session',
			'authTimestamp' => Env::$timestamp,
			'httpRequestHash' => $this->httpObject->httpReqData['httpRequestHash']
		];

		foreach ($this->httpObject->httpRequestObject->activeRequestData['userData'] as $userDataKey => &$userDataKeyValue) {
			$userSessionData[$userDataKey] = $userDataKeyValue;
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
		$httpRequestHash = $this->httpObject->httpReqData['httpRequestHash'];

		$customerUserTokenKey = null;
		$customerUserToken = null;

		$authFound = false;
		$authFoundData = [];

		$customerUserConcurrencyKey = null;
		$customerUserConcurrencyData = null;

		$customerUserTokenKey = CacheServerKey::customerUserToken(
			customerId: $this->httpObject->httpRequestObject->customerId,
			customerUserId: $this->httpObject->httpRequestObject->customerUserId
		);

		if (
			$this->cacheExist(
				cacheKey: $customerUserTokenKey
			)
		) {
			$customerUserToken = $this->cacheGet(
				cacheKey: $customerUserTokenKey
			);
		}

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_concurrent_login'
			)
		) {
			$customerUserConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpRequestObject->customerId,
				customerUserId: $this->httpObject->httpRequestObject->customerUserId
			);

			if (
				$this->cacheExist(
					cacheKey: $customerUserConcurrencyKey
				)
			) {
				if ($this->httpObject->httpRequestObject->sessionObject === Constant::$NULL) {
					$this->httpObject->httpRequestObject->sessionObject = new Session();
					$this->httpObject->httpRequestObject->sessionObject->sessionDomain = $this->httpObject->httpReqData['server']['domainName'];
					$this->httpObject->httpRequestObject->sessionObject->initSessionHandler(
						customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
						options: []
					);
				}
				$customerUserConcurrencyData = $this->cacheGet(
					cacheKey: $customerUserConcurrencyKey
				);

				foreach ($customerUserConcurrencyData as $authId => $authData) {
					if (
						$authData['authMode'] === 'Token'
						&& !$this->cacheExist(
							cacheKey: CacheServerKey::token(
								token: $authId
							)
						)
					) {
						unset($customerUserConcurrencyData[$authId]);
						continue;
					}
					if ($authData['authMode'] === 'Session') {
						$timeLeft = Env::$timestamp - $authData['authTimestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							$this->httpObject->httpRequestObject->sessionObject->deleteSession(
								sessionId: $authId
							);
							unset($customerUserConcurrencyData[$authId]);
							continue;
						}
					}
					if (
						$customerUserToken !== Constant::$NULL
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
				$customerUserToken !== Constant::$NULL
				&& $this->cacheExist(
					cacheKey: CacheServerKey::token(
						token: $customerUserToken
					)
				)
			) {
				$authId = $customerUserToken;
				$authData = $this->cacheGet(
					cacheKey: CacheServerKey::token(
						token: $customerUserToken
					)
				);
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

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_concurrent_login'
			)
		) {
			if (
				count(
					value: $customerUserConcurrencyData
				) >= Env::$maxConcurrentLogin
			) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$customerUserConcurrencyKey = $customerUserConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpRequestObject->customerId,
				customerUserId: $this->httpObject->httpRequestObject->customerUserId
			);
			$this->cacheSet(
				cacheKey: $customerUserConcurrencyKey,
				cacheValue: $customerUserConcurrencyData,
				cacheExpire: Env::$concurrentAccessInterval
			);
		}

		$timeLeft = Env::$timestamp - $authFoundData['authTimestamp'];
		$output = [
			'Token' => $authFoundData['authId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', (Constant::$TOKEN_EXPIRY_TIME - $timeLeft))
		];

		$this->outputDetail(
			output: $output
		);
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
		$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
			objectKey: 'Results',
			data: $output
		);
	}

	/**
	 * Outputs active/newly generated session detail
	 * 
	 * @return void
	 */
	private function startSession(): void
	{
		$httpRequestHash = $this->httpObject->httpReqData['httpRequestHash'];

		$customerUserSessionIdKey = null;
		$customerUserSessionId = null;

		$authFound = false;
		$authFoundData = [];

		$customerUserConcurrencyKey = null;
		$customerUserConcurrencyData = null;

		$customerUserSessionIdKey = CacheServerKey::customerUserSessionId(
			customerId: $this->httpObject->httpRequestObject->customerId,
			customerUserId: $this->httpObject->httpRequestObject->customerUserId
		);

		if (
			$this->cacheExist(
				cacheKey: $customerUserSessionIdKey
			)
		) {
			$customerUserSessionId = $this->cacheGet(
				cacheKey: $customerUserSessionIdKey
			);
		}

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_concurrent_login'
			)
		) {
			$customerUserConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpRequestObject->customerId,
				customerUserId: $this->httpObject->httpRequestObject->customerUserId
			);

			if (
				$this->cacheExist(
					cacheKey: $customerUserConcurrencyKey
				)
			) {
				if ($this->httpObject->httpRequestObject->sessionObject === Constant::$NULL) {
					$this->httpObject->httpRequestObject->sessionObject = new Session();
					$this->httpObject->httpRequestObject->sessionObject->sessionDomain = $this->httpObject->httpReqData['server']['domainName'];
					$this->httpObject->httpRequestObject->sessionObject->initSessionHandler(
						customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
						options: []
					);
				}
				$customerUserConcurrencyData = $this->cacheGet(
					cacheKey: $customerUserConcurrencyKey
				);

				foreach ($customerUserConcurrencyData as $authId => $authData) {
					if (
						$authData['authMode'] === 'Token'
						&& !$this->cacheExist(
							cacheKey: CacheServerKey::token(
								token: $authId
							)
						)
					) {
						unset($customerUserConcurrencyData[$authId]);
						continue;
					}
					if ($authData['authMode'] === 'Session') {
						$timeLeft = Env::$timestamp - $authData['authTimestamp'];
						if ((Constant::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
							$this->httpObject->httpRequestObject->sessionObject->deleteSession(
								sessionId: $authId
							);
							unset($customerUserConcurrencyData[$authId]);
							continue;
						}
					}
					if (
						$customerUserSessionId !== Constant::$NULL
						&& $customerUserSessionId === $authId
						&& $authData['httpRequestHash'] === $httpRequestHash
					) {
						$authFoundData = $authData;
						$authFound = true;
					}
				}
			}
		} else {
			if ($this->httpObject->httpRequestObject->sessionObject === Constant::$NULL) {
				$this->httpObject->httpRequestObject->sessionObject = new Session();
				$this->httpObject->httpRequestObject->sessionObject->sessionDomain = $this->httpObject->httpReqData['server']['domainName'];
				$this->httpObject->httpRequestObject->sessionObject->initSessionHandler(
					customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
					options: []
				);
			}
			$this->httpObject->httpRequestObject->sessionObject->sessionStartReadonly();
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

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_concurrent_login'
			)
		) {
			if (
				count(
					value: $customerUserConcurrencyData
				) >= Env::$maxConcurrentLogin
			) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$customerUserConcurrencyKey = $customerUserConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpRequestObject->customerId,
				customerUserId: $this->httpObject->httpRequestObject->customerUserId
			);
			$this->cacheSet(
				cacheKey: $customerUserConcurrencyKey,
				cacheValue: $customerUserConcurrencyData,
				cacheExpire: Env::$concurrentAccessInterval
			);
		}

		$timeLeft = Env::$timestamp - $authFoundData['authTimestamp'];
		$output = [
			'SessionId' => $authFoundData['authId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', (Constant::$TOKEN_EXPIRY_TIME - $timeLeft))
		];

		$this->outputDetail(
			output: $output
		);
	}

	/**
	 * Global cache key exist
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	private function cacheExist(
		$cacheKey
	): mixed {
		if ($this->httpObject->httpRequestObject->isPrivateRequest) {
			return $this->httpObject->httpRequestObject->customerCacheObject->cacheExist(
				cacheKey: $cacheKey
			);
		}

		return false;
	}

	/**
	 * Get global cache key
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	private function cacheGet(
		$cacheKey
	): mixed {
		if ($this->httpObject->httpRequestObject->isPrivateRequest) {
			return $this->httpObject->httpRequestObject->customerCacheObject->cacheGet(
				cacheKey: $cacheKey
			);
		}

		return false;
	}

	/**
	 * Set global cache key
	 * 
	 * @param string $cacheKey    Cache key
	 * @param mixed  $cacheValue  Cache value
	 * @param int    $cacheExpire Seconds to expire. Default 0 - doesn't expire
	 * 
	 * @return mixed
	 */
	private function cacheSet(
		$cacheKey,
		$cacheValue,
		$cacheExpire = 0
	): mixed {
		if ($this->httpObject->httpRequestObject->isPrivateRequest) {
			return $this->httpObject->httpRequestObject->customerCacheObject->cacheSet(
				cacheKey: $cacheKey,
				cacheValue: $cacheValue,
				cacheExpire: $cacheExpire
			);
		}

		return false;
	}

	/**
	 * Delete global cache key
	 * 
	 * @param string $cacheKey Cache key
	 * 
	 * @return mixed
	 */
	private function cacheDelete(
		$cacheKey
	): mixed {
		if ($this->httpObject->httpRequestObject->isPrivateRequest) {
			return $this->httpObject->httpRequestObject->customerCacheObject->cacheDelete(
				cacheKey: $cacheKey
			);
		}

		return false;
	}
}
