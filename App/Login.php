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
		return Constant::$TRUE;
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
			$this->httpObject->Env->CUSTOMER_ENABLE_LIMITING_LOGGED_IN_USER_PER_IP
			&& !empty($this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count'])
			&& !empty($this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count_window'])
		) {
			$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
				rateLimitPrefix: $this->httpObject->Env->RATE_LIMIT_IP_USER_PREFIX,
				rateLimitMaxRequest: $this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count'],
				rateLimitMaxRequestWindow: $this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count_window'],
				rateLimitKey: $this->httpObject->httpReqData['server']['httpRequestIp']
			);
		}

		if (
			$this->httpObject->Env->CUSTOMER_ENABLE_PRIVATE_WEB_REQUEST
			&& $this->httpObject->httpRequestObject->isPrivateWebDomain
		) {
			$this->startSession();
		} elseif (
			$this->httpObject->Env->CUSTOMER_ENABLE_PRIVATE_API_REQUEST
			&& $this->httpObject->httpRequestObject->isPrivateApiDomain
		) {
			$this->outputTokenData();
		} else {
			throw new \Exception(
				message: "Invalid domain: '{$this->httpObject->httpReqData['server']['domainName']}' to login",
				code: HttpStatus::$BadRequest
			);
		}

		return Constant::$TRUE;
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
		$this->payload = $this->httpObject->httpRequestObject->dataDecodeObject->getObject();

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
		$customerUserKey = CacheServerKey::customerUsername(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
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
		$this->httpObject->httpReqData['active']['customerUserData'] = $userData;
		$this->httpObject->httpReqData['active']['customerUserId'] = $userData['customer_user_id'];
		$this->httpObject->httpReqData['active']['customerUserGroupId'] = $userData['customer_user_group_id'];
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
			!empty($this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count'])
			&& !empty($this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count_window'])
		) {
			$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
				rateLimitPrefix: $this->httpObject->Env->RATE_LIMIT_USER_LOGIN_PREFIX,
				rateLimitMaxRequest: $this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count'],
				rateLimitMaxRequestWindow: $this->httpObject->httpReqData['active']['customerData']['customer_limiting_logged_in_user_per_ip_count_window'],
				rateLimitKey: $this->httpObject->httpReqData['server']['httpRequestIp'] . ':' . $this->customer_user_username
			);
		}

		// get hash from cache and compares with password
		if (
			!password_verify(
				password: $this->customer_user_password,
				hash: $this->httpObject->httpReqData['active']['customerUserData']['customer_user_password_hash']
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
	private function generateApiToken(): array
	{
		//generates a crypto-secure 64 characters long
		while (Constant::$TRUE) {
			$apiToken = bin2hex(
				string: random_bytes(
					length: 32
				)
			);

			if (
				!$this->cacheExist(
					cacheKey: CacheServerKey::apiToken(
						apiToken: $apiToken
					)
				)
			) {
				$this->cacheSet(
					cacheKey: CacheServerKey::apiToken(
						apiToken: $apiToken
					),
					cacheValue: '{}',
					cacheExpire: $this->httpObject->Env->TOKEN_LIFETIME
				);
				$activeData = [
					'authId' => $apiToken,
					'authMode' => 'API Token',
					'authTimestamp' => Env::$timestamp,
					'httpRequestHash' => $this->httpObject->httpReqData['httpRequestHash']
				];
				break;
			}
		}

		foreach ($this->httpObject->httpReqData['active']['customerUserData'] as $activeDataKey => &$activeDataKeyValue) {
			$activeData[$activeDataKey] = $activeDataKeyValue;
		}

		$this->cacheSet(
			cacheKey: CacheServerKey::apiToken(
				apiToken: $activeData['authId']
			),
			cacheValue: $activeData,
			cacheExpire: $this->httpObject->Env->TOKEN_LIFETIME
		);

		return $activeData;
	}

	/**
	 * Outputs active/newly generated token detail
	 * 
	 * @return void
	 */
	private function outputTokenData(): void
	{
		if ($this->httpObject->Env->CUSTOMER_ENABLE_PRIVATE_WEB_REQUEST) {
			$this->httpObject->sessionObject = new Session(
				httpObject: $this->httpObject
			);
			$this->httpObject->sessionObject->initSessionHandler();
		}

		$httpRequestHash = $this->httpObject->httpReqData['httpRequestHash'];

		$customerUserTokenKey = Constant::$NULL;
		$customerUserToken = Constant::$NULL;

		$authFound = Constant::$FALSE;
		$authFoundData = [];

		$customerUserConcurrencyKey = Constant::$NULL;
		$customerUserConcurrencyData = Constant::$NULL;

		$customerUserTokenKey = CacheServerKey::customerUserApiToken(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			customerUserId: $this->httpObject->httpReqData['active']['customerUserId']
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

		if ($this->httpObject->Env->CUSTOMER_ENABLE_LIMITING_SUCCESSFULL_LOGIN_PER_USER) {
			$customerUserConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpReqData['active']['customerId'],
				customerUserId: $this->httpObject->httpReqData['active']['customerUserId']
			);

			if (
				$this->cacheExist(
					cacheKey: $customerUserConcurrencyKey
				)
			) {
				$customerUserConcurrencyData = $this->cacheGet(
					cacheKey: $customerUserConcurrencyKey
				);

				foreach ($customerUserConcurrencyData as $authId => $authData) {
					if (
						$authData['authMode'] === 'API Token'
						&& !$this->cacheExist(
							cacheKey: CacheServerKey::apiToken(
								apiToken: $authId
							)
						)
					) {
						unset($customerUserConcurrencyData[$authId]);
						continue;
					}
					if (
						$this->httpObject->Env->CUSTOMER_ENABLE_PRIVATE_WEB_REQUEST
						&& $authData['authMode'] === 'Web Session'
					) {
						$timeLeft = Env::$timestamp - $authData['authTimestamp'];
						if (($this->httpObject->Env->TOKEN_LIFETIME - $timeLeft) <= 0) {
							$this->httpObject->sessionObject->deleteSession(
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
						$authFound = Constant::$TRUE;
					}
				}
			}
		} else {
			if (
				$customerUserToken !== Constant::$NULL
				&& $this->cacheExist(
					cacheKey: CacheServerKey::apiToken(
						apiToken: $customerUserToken
					)
				)
			) {
				$authId = $customerUserToken;
				$authData = $this->cacheGet(
					cacheKey: CacheServerKey::apiToken(
						apiToken: $customerUserToken
					)
				);
				if ($authData['httpRequestHash'] === $httpRequestHash) {
					$authFoundData = $authData;
					$authFound = Constant::$TRUE;
				}
			}
		}

		if (!$authFound) {
			$authFoundData = $this->generateApiToken();
			$authFound = Constant::$TRUE;

			$this->cacheSet(
				cacheKey: $customerUserTokenKey,
				cacheValue: $authFoundData['authId'],
				cacheExpire: $this->httpObject->Env->TOKEN_LIFETIME
			);

			$customerUserConcurrencyData[$authFoundData['authId']] = $authFoundData;
		}

		if ($this->httpObject->Env->CUSTOMER_ENABLE_LIMITING_SUCCESSFULL_LOGIN_PER_USER) {
			if (
				count(
					value: $customerUserConcurrencyData
				) >= $this->httpObject->httpReqData['active']['customerData']['customer_limiting_login_successfull_per_user_count']
			) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . $this->httpObject->httpReqData['active']['customerData']['customer_limiting_login_successfull_per_user_count'] . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$customerUserConcurrencyKey = $customerUserConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpReqData['active']['customerId'],
				customerUserId: $this->httpObject->httpReqData['active']['customerUserId']
			);
			$this->cacheSet(
				cacheKey: $customerUserConcurrencyKey,
				cacheValue: $customerUserConcurrencyData,
				cacheExpire: $this->httpObject->httpReqData['active']['customerData']['customer_limiting_login_successfull_per_user_count']
			);
		}

		$timeLeft = Env::$timestamp - $authFoundData['authTimestamp'];
		$output = [
			'authMode' => 'API Token',
			'authId' => $authFoundData['authId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', ($this->httpObject->Env->TOKEN_LIFETIME - $timeLeft))
		];

		$this->outputDetail(
			output: $output
		);
	}

	/**
	 * Generates session
	 * 
	 * @return array
	 */
	private function generateSession(): array
	{
		if (!$this->httpObject->httpRequestObject->isPrivateWebDomain) {
			return [];
		}

		$activeData = array_merge(
			[
				'authId' => session_id(),
				'authMode' => 'Web Session',
				'authTimestamp' => Env::$timestamp,
				'httpRequestHash' => $this->httpObject->httpReqData['httpRequestHash']
			],
			$this->httpObject->httpReqData['active']['customerUserData']
		);

		return $activeData;
	}

	/**
	 * Outputs active/newly generated session detail
	 * 
	 * @return void
	 */
	private function startSession(): void
	{
		$this->httpObject->sessionObject = new Session(
			httpObject: $this->httpObject
		);
		$this->httpObject->sessionObject->initSessionHandler();
		$this->httpObject->sessionObject->startReadWrite();

		$httpRequestHash = $this->httpObject->httpReqData['httpRequestHash'];

		$customerUserSessionIdKey = Constant::$NULL;
		$customerUserSessionId = Constant::$NULL;

		$authFound = Constant::$FALSE;
		$authFoundData = [];

		$customerUserConcurrencyKey = Constant::$NULL;
		$customerUserConcurrencyData = Constant::$NULL;

		$customerUserSessionIdKey = CacheServerKey::customerUserSessionId(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			customerUserId: $this->httpObject->httpReqData['active']['customerUserId']
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
			isset(
				$this->httpObject->httpReqData['header']['cookie'][
					$this->httpObject->Env->SESSION_COOKIE_NAME
				]
			)
		) {
			$this->httpObject->sessionObject->startReadonly();
			if ($customerUserSessionId === session_id()) {
				if ($_SESSION['httpRequestHash'] === $httpRequestHash) {
					$authFoundData = $_SESSION;
					$authFound = Constant::$TRUE;
				}
			}
		} else {
			$customerUserConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpReqData['active']['customerId'],
				customerUserId: $this->httpObject->httpReqData['active']['customerUserId']
			);

			if (
				$this->cacheExist(
					cacheKey: $customerUserConcurrencyKey
				)
			) {
				$customerUserConcurrencyData = $this->cacheGet(
					cacheKey: $customerUserConcurrencyKey
				);

				foreach ($customerUserConcurrencyData as $authId => $authData) {
					if (
						$this->httpObject->Env->CUSTOMER_ENABLE_PRIVATE_API_REQUEST
						&& $authData['authMode'] === 'API Token'
						&& !$this->cacheExist(
							cacheKey: CacheServerKey::apiToken(
								apiToken: $authId
							)
						)
					) {
						unset($customerUserConcurrencyData[$authId]);
						continue;
					}
					if ($authData['authMode'] === 'Web Session') {
						$timeLeft = Env::$timestamp - $authData['authTimestamp'];
						if (($this->httpObject->Env->TOKEN_LIFETIME - $timeLeft) <= 0) {
							$this->httpObject->sessionObject->deleteSession(
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
						$authFound = Constant::$TRUE;
					}
				}
			}
		}

		if (!$authFound) {
			$authFoundData = $this->generateSession();
			$authFound = Constant::$TRUE;

			$_SESSION = $authFoundData;

			$this->cacheSet(
				cacheKey: $customerUserSessionIdKey,
				cacheValue: $authFoundData['authId'],
				cacheExpire: $this->httpObject->Env->TOKEN_LIFETIME
			);

			$customerUserConcurrencyData[$authFoundData['authId']] = $authFoundData;
		}

		if ($this->httpObject->Env->CUSTOMER_ENABLE_LIMITING_SUCCESSFULL_LOGIN_PER_USER) {
			if (
				count(
					value: $customerUserConcurrencyData
				) >= $this->httpObject->httpReqData['active']['customerData']['customer_limiting_login_successfull_per_user_count']
			) {
				throw new \Exception(
					message: 'Account already in use. '
						. 'Please try after ' . $this->httpObject->httpReqData['active']['customerData']['customer_limiting_login_successfull_per_user_count'] . ' second(s)',
					code: HttpStatus::$Conflict
				);
			}
			$customerUserConcurrencyKey = $customerUserConcurrencyKey ?? CacheServerKey::customerUserConcurrency(
				customerId: $this->httpObject->httpReqData['active']['customerId'],
				customerUserId: $this->httpObject->httpReqData['active']['customerUserId']
			);
			$this->cacheSet(
				cacheKey: $customerUserConcurrencyKey,
				cacheValue: $customerUserConcurrencyData,
				cacheExpire: $this->httpObject->httpReqData['active']['customerData']['customer_limiting_login_successfull_per_user_count']
			);
		}

		$timeLeft = Env::$timestamp - $authFoundData['authTimestamp'];
		$output = [
			'authMode' => 'Web Session',
			'authId' => $authFoundData['authId'],
			'Expires' => date('d\ \d\a\y H\ \h\o\u\r i\ \m\i\n s\ \s\e\c', ($this->httpObject->Env->TOKEN_LIFETIME - $timeLeft))
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
			return $this->httpObject->cacheObject->cacheExist(
				cacheKey: $cacheKey
			);
		}

		return Constant::$FALSE;
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
		if ($this->httpObject->httpRequestObject->isPublicRequest) {
			throw new \Exception(
				message: 'Logins are restricted via public domains',
				code: HttpStatus::$NotFound
			);
		}
		return $this->httpObject->cacheObject->cacheGet(
			cacheKey: $cacheKey
		);
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
		if ($this->httpObject->httpRequestObject->isPublicRequest) {
			throw new \Exception(
				message: 'Logins are restricted via public domains',
				code: HttpStatus::$NotFound
			);
		}
		return $this->httpObject->cacheObject->cacheSet(
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
	private function cacheDelete(
		$cacheKey
	): mixed {
		if ($this->httpObject->httpRequestObject->isPublicRequest) {
			throw new \Exception(
				message: 'Logins are restricted via public domains',
				code: HttpStatus::$NotFound
			);
		}
		return $this->httpObject->cacheObject->cacheDelete(
			cacheKey: $cacheKey
		);
	}
}
