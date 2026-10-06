<?php

/**
 * Middleware
 * php version 8.3
 *
 * @category  Middleware
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheServerKey;
use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\SessionHandler\Session;

/**
 * Class handling detail for Auth middleware
 * php version 8.3
 *
 * @category  Auth_Middleware
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Auth
{
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
	 * Load User Data
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function loadUserData(): void
	{
		if (isset($this->httpObject->httpRequestObject->activeRequestData['customerUserData'])) {
			return;
		}

		if (
			$this->httpObject->httpRequestObject->isPrivateSessionDomain
			&& isset($this->httpObject->httpReqData['header']['cookie'][Env::$config[$this->httpObject->httpReqData['current']['customerId']]->SESSION_COOKIE_NAME])
		) {
			$this->httpObject->httpRequestObject->sessionObject = new Session(
				httpObject: $this->httpObject
			);
			$this->httpObject->httpRequestObject->sessionObject->initSessionHandler();
			$this->httpObject->httpRequestObject->sessionObject->startReadonly();

			$this->httpObject->httpRequestObject->activeRequestData['customerUserData'] = $_SESSION;
		} elseif (
			$this->httpObject->httpRequestObject->isPrivateTokenDomain
			&& isset($this->httpObject->httpReqData['header']['tokenHeader'])
			&& $this->httpObject->httpReqData['header']['tokenHeader'] !== Constant::$NULL
		) {
			if (
				!preg_match(
					pattern: '/Bearer\s(\S+)/',
					subject: $this->httpObject->httpReqData['header']['tokenHeader'],
					matches: $matches
				)
			) {
				throw new \Exception(
					message: 'Token missing',
					code: HttpStatus::$BadRequest
				);
			}
			$this->httpObject->httpRequestObject->activeRequestData['authId'] = $matches[1];
			$tokenKey = CacheServerKey::token(
				token: $this->httpObject->httpRequestObject->activeRequestData['authId']
			);
			if (
				!$this->httpObject->httpRequestObject->cacheServerObject->cacheExist(
					cacheKey: $tokenKey
				)
			) {
				throw new \Exception(
					message: 'Please login',
					code: HttpStatus::$BadRequest
				);
			}
			$this->httpObject->httpRequestObject->activeRequestData['customerUserData'] = $this->httpObject->httpRequestObject->cacheServerObject->cacheGet(
				cacheKey: $tokenKey
			);
		} else {
			throw new \Exception(
				message: 'Please login',
				code: HttpStatus::$BadRequest
			);
		}

		if (($this->httpObject->httpRequestObject->activeRequestData['customerUserData']['authTimestamp'] + Constant::$TOKEN_EXPIRY_TIME) <= Env::$timestamp) {
			throw new \Exception(
				message: 'Login has timed out. Please login ' . $this->httpObject->httpRequestObject->activeRequestData['customerUserData']['authTimestamp'],
				code: HttpStatus::$BadRequest
			);
		}

		// if ($this->httpObject->httpRequestObject->activeRequestData['customerUserData']['httpRequestHash'] !== $this->httpObject->httpReqData['httpRequestHash']) {
		// 	throw new \Exception(
		// 		message: 'Current Browser or the Device location not matching with Browser or the Device location during Login',
		// 		code: HttpStatus::$PreconditionFailed
		// 	);
		// }

		$this->httpObject->httpReqData['current']['customerUserId'] = $this->httpObject->httpRequestObject->activeRequestData['customerUserData']['customer_user_id'];
		$this->httpObject->httpReqData['current']['customerUserGroupId'] = $this->httpObject->httpRequestObject->activeRequestData['customerUserData']['customer_user_group_id'];
	}

	/**
	 * Load Group Data
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function loadGroupData(): void
	{
		if (isset($this->httpObject->httpRequestObject->activeRequestData['groupData'])) {
			return;
		}

		// Load groupData
		$groupCacheKey = CacheServerKey::customerGroup(
			customerId: $this->httpObject->httpReqData['current']['customerId'],
			customerUserGroupId: $this->httpObject->httpReqData['current']['customerUserGroupId']
		);
		if (
			!$this->httpObject->httpRequestObject->cacheServerObject->cacheExist(
				cacheKey: $groupCacheKey
			)
		) {
			throw new \Exception(
				message: "Cache '{$groupCacheKey}' missing",
				code: HttpStatus::$InternalServerError
			);
		}

		$this->httpObject->httpRequestObject->activeRequestData['groupData'] = $this->httpObject->httpRequestObject->cacheServerObject->cacheGet(
			cacheKey: $groupCacheKey
		);
	}
}
