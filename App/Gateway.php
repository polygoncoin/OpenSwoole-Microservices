<?php

/**
 * Gateway
 * php version 8.3
 *
 * @category  Gateway
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Gateway - contains checks like IP and Rate Limiting functions
 * php version 8.3
 *
 * @category  Gateway
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Gateway
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
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		$this->http->req->loadCustomerData();

		if ($this->http->req->isPrivateRequest) {
			$this->http->req->auth->loadUserData();
			CommonFunction::checkClosedWebRequestCidr(http: $this->http);
		}
		$this->rateLimitRequest();

		return true;
	}

	/**
	 * Rate Limit request
	 *
	 * @return void
	 */
	private function rateLimitRequest(): void
	{
		if ($this->http->req->isPrivateRequest) {
			// IP Rate Limiting
			$this->rateLimitIp();

			// Customer Rate Limiting
			$this->rateLimitCustomer();

			// Group Rate Limiting
			$this->rateLimitGroup();

			// User Rate Limiting
			$this->rateLimitUser();

			// User Rate Limiting request Delay
			$this->rateLimitUserRequest();
		}
	}

	/**
	 * Rate Limit Customer
	 *
	 * @return void
	 */
	private function rateLimitCustomer(): void
	{
		if (
			!Env::$enableRateLimitForCustomer
			|| empty($this->http->req->s['customerData']['rateLimitMaxRequest'])
			|| empty($this->http->req->s['customerData']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitCustomerPrefix = Env::$rateLimitCustomerPrefix;
		$rateLimitMaxRequest =
				$this->http->req->s['customerData']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
				$this->http->req->s['customerData']['rateLimitMaxRequestWindow'];
		$rateLimitKey = $this->http->req->customerId;

		$this->http->req->rateLimiter->checkRateLimit(
			rateLimitPrefix: $rateLimitCustomerPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			rateLimitKey: $rateLimitKey
		);
	}

	/**
	 * Rate Limit Customer Group
	 *
	 * @return void
	 */
	private function rateLimitGroup(): void
	{
		if (
			!Env::$enableRateLimitForGroup
			|| empty($this->http->req->s['groupData']['rateLimitMaxRequest'])
			|| empty($this->http->req->s['groupData']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitGroupPrefix =
			Env::$rateLimitGroupPrefix;
		$rateLimitMaxRequest =
			$this->http->req->s['groupData']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
			$this->http->req->s['groupData']['rateLimitMaxRequestWindow'];
		$rateLimitKey = $this->http->req->customerId . ':'
			. $this->http->req->userId;

		$this->http->req->rateLimiter->checkRateLimit(
			rateLimitPrefix: $rateLimitGroupPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			rateLimitKey: $rateLimitKey
		);
	}

	/**
	 * Rate Limit Customer Group User
	 *
	 * @return void
	 */
	private function rateLimitUser(): void
	{
		if (
			!Env::$enableRateLimitForUser
			|| empty($this->http->req->s['userData']['rateLimitMaxRequest'])
			|| empty($this->http->req->s['userData']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitUserPrefix = Env::$rateLimitUserPrefix;
		$rateLimitMaxRequest =
			$this->http->req->s['groupData']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
			$this->http->req->s['groupData']['rateLimitMaxRequestWindow'];
		$rateLimitKey = $this->http->req->customerId . ':'
			. $this->http->req->userId;

		$this->http->req->rateLimiter->checkRateLimit(
			rateLimitPrefix: $rateLimitUserPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			rateLimitKey: $rateLimitKey
		);
	}

	/**
	 * Rate Limit Customer Group User request Delay
	 *
	 * @return void
	 */
	private function rateLimitUserRequest(): void
	{
		if (!Env::$enableRateLimitForUserRequest) {
			return;
		}

		$rateLimitUserPrefix = Env::$rateLimitUserRequestPrefix;
		$rateLimitMaxRequest = Env::$rateLimitUserMaxRequest;
		$rateLimitMaxRequestWindow = Env::$rateLimitUserMaxRequestWindow;
		$rateLimitKey = $this->http->req->customerId . ':'
			. $this->http->req->userId;

		$this->http->req->rateLimiter->checkRateLimit(
			rateLimitPrefix: $rateLimitUserPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			rateLimitKey: $rateLimitKey
		);
	}

	/**
	 * Rate Limit request from source IP
	 *
	 * @return void
	 */
	private function rateLimitIp(): void
	{
		if (!Env::$enableRateLimitForIp) {
			return;
		}

		$rateLimitIPPrefix = Env::$rateLimitIPPrefix;
		$rateLimitIPMaxRequest = Env::$rateLimitIPMaxRequest;
		$rateLimitIPMaxRequestWindow = Env::$rateLimitIPMaxRequestWindow;
		$rateLimitKey = $this->http->req->customerId . ':' . $this->http->httpReqData['server']['httpRequestIP'];

		$this->http->req->rateLimiter->checkRateLimit(
			rateLimitPrefix: $rateLimitIPPrefix,
			rateLimitMaxRequest: $rateLimitIPMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitIPMaxRequestWindow,
			rateLimitKey: $rateLimitKey
		);
	}
}
