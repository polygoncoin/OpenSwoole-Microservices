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

namespace Microservices\App\Middleware;

use Microservices\App\CacheServerKey;
use Microservices\App\CommonFunction;
use Microservices\App\DbCommonFunction;
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
			$this->http->req->s['token'] = session_id();
		} elseif (
			($this->http->httpReqData['header']['tokenHeader'] !== null)
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
			$this->http->req->s['token'] = $matches[1];
			$tokenKey = CacheServerKey::token(
				token: $this->http->req->s['token']
			);
			if (
				!$this->http->req->clientCacheObj->cacheExist(
					cacheKey: $tokenKey
				)
			) {
				throw new \Exception(
					message: 'Token expired',
					code: HttpStatus::$BadRequest
				);
			}
			$this->http->req->s['userData'] = json_decode(
				json: $this->http->req->clientCacheObj->cacheGet(
					cacheKey: $tokenKey
				),
				associative: true
			);
		}
		$this->http->req->userId = $this->http->req->s['userData']['id'];
		$this->http->req->groupId = $this->http->req->s['userData']['group_id'];

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableConcurrentLogin')) {
			$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
				customerId: $this->http->req->customerId,
				userId: $this->http->req->userId
			);
			if ($this->http->req->clientCacheObj->cacheExist(cacheKey: $userConcurrencyKey)) {
				$userConcurrencyKeyData = $this->http->req->clientCacheObj->cacheGet(
					cacheKey: $userConcurrencyKey
				);
				if ($userConcurrencyKeyData !== $this->http->req->s['token']) {
					throw new \Exception(
						message: 'Account already in use. '
							. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
						code: HttpStatus::$Conflict
					);
				}
			} else {
				$this->http->req->clientCacheObj->cacheSet(
					cacheKey: $userConcurrencyKey,
					cacheValue: $this->http->req->s['token'],
					cacheExpire: Env::$concurrentAccessInterval
				);
			}
		} else {
			if ($this->http->req->s['userData']['httpRequestHash'] !== $this->http->httpReqData['httpRequestHash']) {
				throw new \Exception(
					message: 'Token not supported from this Browser/Device',
					code: HttpStatus::$PreconditionFailed
				);
			}
		}
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
		if (
			empty($this->http->req->userId)
			|| empty($this->http->req->userId)
		) {
			throw new \Exception(
				message: 'Invalid session',
				code: HttpStatus::$InternalServerError
			);
		}

		$gKey = CacheServerKey::customerGroup(
			customerId: $this->http->req->customerId,
			groupId: $this->http->req->groupId
		);
		if (!$this->http->req->clientCacheObj->cacheExist(cacheKey: $gKey)) {
			throw new \Exception(
				message: "Cache '{$gKey}' missing",
				code: HttpStatus::$InternalServerError
			);
		}

		$this->http->req->s['groupData'] = json_decode(
			json: $this->http->req->clientCacheObj->cacheGet(
				cacheKey: $gKey
			),
			associative: true
		);
	}
}
