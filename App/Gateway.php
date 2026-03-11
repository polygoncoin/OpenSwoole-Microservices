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

use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Functions;
use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;

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
	 * CIDR checked boolean flag
	 *
	 * @var bool
	 */
	public $cidrChecked = false;

	/**
	 * Rate Limiter
	 *
	 * @var null|RateLimiter
	 */
	private $rateLimiter = null;

	/**
	 * Rate Limit check flag
	 *
	 * @var bool
	 */
	private $rateLimitChecked = false;

	/**
	 * Api common Object
	 *
	 * @var null|Common
	 */
	private $api = null;

	/**
	 * Constructor
	 *
	 * @param Common $api
	 */
	public function __construct(Common &$api)
	{
		$this->api = &$api;
	}

	/**
	 * Initialize Gateway
	 *
	 * @return void
	 */
	public function initGateway(): void
	{
		$this->api->req->loadCustomerDetails();

		if (!$this->api->req->open) {
			$this->api->req->auth->loadUserDetails();
			$this->checkCidr();
		}
		$this->checkRateLimits();
	}

	/**
	 * Check Rate Limits
	 *
	 * @return void
	 */
	private function checkRateLimits(): void
	{
		$this->rateLimiter = new RateLimiter();

		// Customer Rate Limiting
		$this->rateLimitCustomer();

		if (!$this->api->req->open) {
			// Group Rate Limiting
			$this->rateLimitGroup();

			// User Rate Limiting
			$this->rateLimitUser();

			// User Rate Limiting Request Delay
			$this->rateLimitUsersRequest();
		}

		// Rate limit open traffic (not limited by allowed IPs/CIDR and allowed
		// Rate Limits to user)
		if ($this->cidrChecked === false && $this->rateLimitChecked === false) {
			// IP Rate Limiting
			$this->rateLimitIp();
		}
	}

	/**
	 * Check Rate Limit
	 *
	 * @param string $rateLimitPrefix        Prefix
	 * @param int    $rateLimitMaxRequest   Max request
	 * @param int    $rateLimitMaxRequestWindow Window in seconds
	 * @param string $key                    Key
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function checkRateLimit(
		$rateLimitPrefix,
		$rateLimitMaxRequest,
		$rateLimitMaxRequestWindow,
		$key
	): bool {
		try {
			$result = $this->rateLimiter->check(
				prefix: $rateLimitPrefix,
				maxRequest: $rateLimitMaxRequest,
				secondsWindow: $rateLimitMaxRequestWindow,
				key: $key
			);

			if ($result['allowed']) {
				// Process the request
				return true;
			} else {
				// Return 429 Too Many Request
				throw new \Exception(
					message: $result['resetAt'] - Env::$timestamp,
					code: HttpStatus::$TooManyRequest
				);
			}
		} catch (\Exception $e) {
			// Handle connection errors
			throw new \Exception(
				message: $e->getMessage(),
				code: $e->getCode()
			);
		}
	}

	/**
	 * Validate remote IP
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function checkCidr(): void
	{
		if (!Env::$enableCidrCheck) {
			return;
		}

		$cCidrKey = CacheKey::cCidr(
			cID: $this->api->req->s['cDetails']['id']
		);
		$gCidrKey = CacheKey::gCidr(
			gID: $this->api->req->s['uDetails']['group_id']
		);
		$uCidrKey = CacheKey::uCidr(
			cID: $this->api->req->s['cDetails']['id'],
			uID: $this->api->req->s['uDetails']['id']
		);
		foreach ([$cCidrKey, $gCidrKey, $uCidrKey] as $key) {
			if (!$this->cidrChecked) {
				$this->cidrChecked = Functions::checkCacheCidr(
					IP: $this->api->req->IP,
					againstCacheKey: $key
				);
			}
		}
	}

	/**
	 * Rate Limit Customer Request
	 *
	 * @return void
	 */
	private function rateLimitCustomer(): void
	{
		if (
			!Env::$enableRateLimitAtCustomerLevel
			|| empty($this->api->req->s['cDetails']['rateLimitMaxRequest'])
			|| empty($this->api->req->s['cDetails']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitCustomerPrefix = Env::$rateLimitCustomerPrefix;
		$rateLimitMaxRequest =
				$this->api->req->s['cDetails']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
				$this->api->req->s['cDetails']['rateLimitMaxRequestWindow'];
		$key = $this->api->req->s['cDetails']['id'];

		$this->rateLimitChecked = $this->checkRateLimit(
			rateLimitPrefix: $rateLimitCustomerPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			key: $key
		);
	}

	/**
	 * Rate Limit Customer Group Request
	 *
	 * @return void
	 */
	private function rateLimitGroup(): void
	{
		if (
			!Env::$enableRateLimitAtGroupLevel
			|| empty($this->api->req->s['gDetails']['rateLimitMaxRequest'])
			|| empty($this->api->req->s['gDetails']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitGroupPrefix =
			Env::$rateLimitGroupPrefix;
		$rateLimitMaxRequest =
			$this->api->req->s['gDetails']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
			$this->api->req->s['gDetails']['rateLimitMaxRequestWindow'];
		$key = $this->api->req->s['cDetails']['id'] . ':'
			. $this->api->req->s['uDetails']['id'];

		$this->rateLimitChecked = $this->checkRateLimit(
			rateLimitPrefix: $rateLimitGroupPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			key: $key
		);
	}

	/**
	 * Rate Limit Customer Group User Request
	 *
	 * @return void
	 */
	private function rateLimitUser(): void
	{
		if (
			!Env::$enableRateLimitAtUserLevel
			|| empty($this->api->req->s['uDetails']['rateLimitMaxRequest'])
			|| empty($this->api->req->s['uDetails']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitUserPrefix = Env::$rateLimitUserPrefix;
		$rateLimitMaxRequest =
			$this->api->req->s['gDetails']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
			$this->api->req->s['gDetails']['rateLimitMaxRequestWindow'];
		$key = $this->api->req->s['cDetails']['id'] . ':'
			. $this->api->req->s['uDetails']['id'] . ':'
			. $this->api->req->s['uDetails']['user_id'];

		$this->rateLimitChecked = $this->checkRateLimit(
			rateLimitPrefix: $rateLimitUserPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			key: $key
		);
	}

	/**
	 * Rate Limit Customer Group User Request Delay
	 *
	 * @return void
	 */
	private function rateLimitUsersRequest(): void
	{
		if (!Env::$enableRateLimitAtUsersRequestLevel) {
			return;
		}

		$rateLimitUserPrefix = Env::$rateLimitUsersRequestPrefix;
		$rateLimitMaxRequest = Env::$rateLimitUsersMaxRequest;
		$rateLimitMaxRequestWindow = Env::$rateLimitUsersMaxRequestWindow;
		$key = $this->api->req->s['cDetails']['id'] . ':'
			. $this->api->req->s['uDetails']['id'];

		$this->rateLimitChecked = $this->checkRateLimit(
			rateLimitPrefix: $rateLimitUserPrefix,
			rateLimitMaxRequest: $rateLimitMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitMaxRequestWindow,
			key: $key
		);
	}

	/**
	 * Rate Limit Request from source IP
	 *
	 * @return void
	 */
	private function rateLimitIp(): void
	{
		if (!Env::$enableRateLimitAtIpLevel) {
			return;
		}

		$rateLimitIPPrefix = Env::$rateLimitIPPrefix;
		$rateLimitIPMaxRequest = Env::$rateLimitIPMaxRequest;
		$rateLimitIPMaxRequestWindow = Env::$rateLimitIPMaxRequestWindow;
		$key = $this->api->req->IP;

		$this->checkRateLimit(
			rateLimitPrefix: $rateLimitIPPrefix,
			rateLimitMaxRequest: $rateLimitIPMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitIPMaxRequestWindow,
			key: $key
		);
	}
}
