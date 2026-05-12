<?php

/**
 * Middleware
 * php version 8.3
 *
 * @category  Middleware
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Middleware;

use Microservices\App\CacheServerKey;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Class handling detail for Auth middleware
 * php version 8.3
 *
 * @category  Auth_Middleware
 * @package   Openswoole_Microservices
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
	 * Request id
	 *
	 * @var null|int
	 */
	private $requestID = null;

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
	 * Load User detail
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function loadUserDetail(): void
	{
		if (isset($this->http->req->s['uDetail'])) {
			return;
		}

		if (
			isset($_SESSION)
			&& isset($_SESSION['id'])
		) {
			$this->http->req->s['uDetail'] = $_SESSION;
			$this->http->req->s['token'] = 'sessions';
			$this->http->req->uID = $_SESSION['id'];
		} elseif (
			($this->http->httpReqDetailArr['header']['tokenHeader'] !== null)
		) {
			if (
				!preg_match(
					pattern: '/Bearer\s(\S+)/',
					subject: $this->http->httpReqDetailArr['header']['tokenHeader'],
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
				!DbCommonFunction::$gCacheServer->cacheExist(
					cacheKey: $tokenKey
				)
			) {
				throw new \Exception(
					message: 'Token expired',
					code: HttpStatus::$BadRequest
				);
			}
			$this->http->req->s['uDetail'] = json_decode(
				json: DbCommonFunction::$gCacheServer->cacheGet(
					cacheKey: $tokenKey
				),
				associative: true
			);
			$this->http->req->uID = $this->http->req->s['uDetail']['id'];
			$this->http->req->gID = $this->http->req->s['uDetail']['group_id'];

			if (Env::$enableConcurrentLogin) {
				$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
					cID: $this->http->req->cID,
					uID: $this->http->req->uID
				);
				if (DbCommonFunction::$gCacheServer->cacheExist(cacheKey: $userConcurrencyKey)) {
					$userConcurrencyKeyData = DbCommonFunction::$gCacheServer->cacheGet(
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
					$this->cacheSet(
						cacheKey: $userConcurrencyKey,
						value: $this->http->req->s['token'],
						expire: Env::$concurrentAccessInterval
					);
				}
			} else {
				if ($this->http->req->s['uDetail']['httpRequestHash'] !== $this->http->httpReqDetailArr['httpRequestHash']) {
					throw new \Exception(
						message: 'Token not supported from this Browser/Device',
						code: HttpStatus::$PreconditionFailed
					);
				}
			}
		}
	}

	/**
	 * Load Group detail
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function loadGroupDetail(): void
	{
		if (isset($this->http->req->s['gDetail'])) {
			return;
		}

		// Load gDetail
		if (
			empty($this->http->req->uID)
			|| empty($this->http->req->uID)
		) {
			throw new \Exception(
				message: 'Invalid session',
				code: HttpStatus::$InternalServerError
			);
		}

		$gKey = CacheServerKey::customerGroup(
			cID: $this->http->req->cID,
			gID: $this->http->req->gID
		);
		if (!DbCommonFunction::$gCacheServer->cacheExist(cacheKey: $gKey)) {
			throw new \Exception(
				message: "Cache '{$gKey}' missing",
				code: HttpStatus::$InternalServerError
			);
		}

		$this->http->req->s['gDetail'] = json_decode(
			json: DbCommonFunction::$gCacheServer->cacheGet(
				cacheKey: $gKey
			),
			associative: true
		);
	}
}
