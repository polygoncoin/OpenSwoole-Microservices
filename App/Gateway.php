<?php

/**
 * Gateway
 * php version 8.3
 *
 * @category  Gateway
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Http;

/**
 * Gateway - contains checks like IP and Rate Limiting functions
 * php version 8.3
 *
 * @category  Gateway
 * @package   Openswoole-Microservices
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
		if ($this->httpObject->httpRequestObject->isPrivateRequest) {
			$this->httpObject->httpRequestObject->authObject->loadUserData();
			CommonFunction::checkPrivateRequestCidr(
				httpObject: $this->httpObject
			);

			$this->rateLimitRequest();
		}

		return Constant::$TRUE;
	}

	/**
	 * Rate Limit request
	 * 
	 * @return void
	 */
	private function rateLimitRequest(): void
	{
		if ($this->httpObject->httpRequestObject->isPrivateRequest) {
			// IP Rate Limiting
			$this->rateLimitIp();

			// Customer Rate Limiting
			$this->rateLimitCustomer();

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
			!Env::$config[$this->httpObject->httpReqData['current']['customerId']]->CUSTOMER_ENABLE_LIMITING
			|| empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_count'])
			|| empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_count_window'])
		) {
			return;
		}

		$RATE_LIMIT_IP_PREFIX = Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_IP_PREFIX;
		$rateLimitMaxRequest =
				$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_count'];
		$rateLimitMaxRequestWindow =
				$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_count_window'];
		$rateLimitKey = $this->httpObject->httpReqData['current']['customerId'];

		$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
			rateLimitPrefix: Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_ROUTE_PREFIX,
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
			!Env::$config[$this->httpObject->httpReqData['current']['customerId']]->CUSTOMER_ENABLE_LIMITING_LOGIN_PER_USER
			|| empty($this->httpObject->httpRequestObject->activeRequestData['customerUserData']['customer_user_count'])
			|| empty($this->httpObject->httpRequestObject->activeRequestData['customerUserData']['customer_user_count_window'])
		) {
			return;
		}

		$RATE_LIMIT_USER_PREFIX = Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_USER_PREFIX;
		$rateLimitMaxRequest =
			$this->httpObject->httpRequestObject->activeRequestData['customerUserData']['customer_user_count'];
		$rateLimitMaxRequestWindow =
			$this->httpObject->httpRequestObject->activeRequestData['customerUserData']['customer_user_count_window'];
		$rateLimitKey = $this->httpObject->httpReqData['current']['customerId'] . ':'
			. $this->httpObject->httpReqData['current']['customerUserId'];

		$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
			rateLimitPrefix: Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_ROUTE_PREFIX,
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
		if (
			!Env::$config[$this->httpObject->httpReqData['current']['customerId']]->CUSTOMER_ENABLE_LIMITING_LOGGED_IN_USER
			|| empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_logged_in_user_count'])
			|| empty($this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_logged_in_user_count_window'])
		) {
			return;
		}

		$RATE_LIMIT_USER_PREFIX = Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_USER_PREFIX;
		$rateLimitMaxRequest = $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_logged_in_user_count'];
		$rateLimitMaxRequestWindow = $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_logged_in_user_count_window'];
		$rateLimitKey = $this->httpObject->httpReqData['current']['customerId'] . ':'
			. $this->httpObject->httpReqData['current']['customerUserId'];

		$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
			rateLimitPrefix: Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_ROUTE_PREFIX,
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
		if (!Env::$config[$this->httpObject->httpReqData['current']['customerId']]->CUSTOMER_ENABLE_LIMITING_PER_IP) {
			return;
		}

		$RATE_LIMIT_IP_PREFIX = Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_IP_PREFIX;
		$customer_ip_count = $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_per_ip_count'];
		$customer_ip_count_window = $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_limiting_per_ip_count_window'];
		$rateLimitKey = $this->httpObject->httpReqData['current']['customerId'] . ':' . $this->httpObject->httpReqData['server']['httpRequestIp'];

		$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
			rateLimitPrefix: Env::$config[$this->httpObject->httpReqData['current']['customerId']]->RATE_LIMIT_ROUTE_PREFIX,
			rateLimitMaxRequest: $customer_ip_count,
			rateLimitMaxRequestWindow: $customer_ip_count_window,
			rateLimitKey: $rateLimitKey
		);
	}
}
