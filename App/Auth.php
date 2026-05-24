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
use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

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
	 * Load User Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function loadUserData(): void
	{
		if (isset($this->http->req->s['userData'])) {
			return;
		}

		if (
			isset($_SESSION)
			&& isset($_SESSION['id'])
		) {
			$this->http->req->s['userData'] = $_SESSION;
			$this->http->req->s['authId'] = session_id();
		} elseif (
			isset($this->http->httpReqData['header']['tokenHeader'])
			&& $this->http->httpReqData['header']['tokenHeader'] !== null
		) {
			if (
				!preg_match(
					pattern: '/Bearer\s(\S+)/',
					subject: $this->http->httpReqData['header']['tokenHeader'],
					matches: $matches
				)
			) {
				throw new \Exception(
					message: 'Token missing',
					code: HttpStatus::$BadRequest
				);
			}
			$this->http->req->s['authId'] = $matches[1];
			$tokenKey = CacheServerKey::token(
				token: $this->http->req->s['authId']
			);
			if (
				!$this->http->req->customerCacheObj->cacheExist(
					cacheKey: $tokenKey
				)
			) {
				throw new \Exception(
					message: 'Please login',
					code: HttpStatus::$BadRequest
				);
			}
			$this->http->req->s['userData'] = json_decode(
				json: $this->http->req->customerCacheObj->cacheGet(
					cacheKey: $tokenKey
				),
				associative: true
			);
		} else {
			throw new \Exception(
				message: 'Please login',
				code: HttpStatus::$BadRequest
			);
		}

		if (($this->http->req->s['userData']['authTimestamp'] + Constant::$TOKEN_EXPIRY_TIME) <= Env::$timestamp) {
			throw new \Exception(
				message: 'Login has timed out. Please login',
				code: HttpStatus::$BadRequest
			);
		}

		if ($this->http->req->s['userData']['httpRequestHash'] !== $this->http->httpReqData['httpRequestHash']) {
			throw new \Exception(
				message: 'Current Browser or the Device location not matching with Browser or the Device location during Login',
				code: HttpStatus::$PreconditionFailed
			);
		}

		$this->http->req->userId = $this->http->req->s['userData']['id'];
		$this->http->req->groupId = $this->http->req->s['userData']['group_id'];
	}

	/**
	 * Load Group Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function loadGroupData(): void
	{
		if (isset($this->http->req->s['groupData'])) {
			return;
		}

		// Load groupData
		$groupCacheKey = CacheServerKey::customerGroup(
			customerId: $this->http->req->customerId,
			groupId: $this->http->req->groupId
		);
		if (!$this->http->req->customerCacheObj->cacheExist(cacheKey: $groupCacheKey)) {
			throw new \Exception(
				message: "Cache '{$groupCacheKey}' missing",
				code: HttpStatus::$InternalServerError
			);
		}

		$this->http->req->s['groupData'] = json_decode(
			json: $this->http->req->customerCacheObj->cacheGet(
				cacheKey: $groupCacheKey
			),
			associative: true
		);
	}
}
