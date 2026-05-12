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
	 * @return void
	 */
	public function init(): void
	{
		$this->http->req->loadCustomerDetail();

		if (!$this->http->req->isOpenToWebRequest) {
			$this->http->req->auth->loadUserDetail();
			$this->checkCidr();
		}
		$this->rateLimitRequest();
	}

	/**
	 * Rate Limit request
	 *
	 * @return void
	 */
	private function rateLimitRequest(): void
	{
		$this->rateLimiter = new RateLimiter();

		// Customer Rate Limiting
		$this->rateLimitCustomer();

		if (!$this->http->req->isOpenToWebRequest) {
			// Group Rate Limiting
			$this->rateLimitGroup();

			// User Rate Limiting
			$this->rateLimitUser();

			// User Rate Limiting request Delay
			$this->rateLimitUserRequest();
		}

		// Rate limit open traffic (not limited by allowed IPs/CIDR and allowed
		// Rate Limit to user)
		if (
			$this->cidrChecked === false
			&& $this->rateLimitChecked === false
		) {
			// IP Rate Limiting
			$this->rateLimitIp();
		}
	}

	/**
	 * Check Rate Limit
	 *
	 * @param string $rateLimitPrefix           Prefix
	 * @param int    $rateLimitMaxRequest       Max request
	 * @param int    $rateLimitMaxRequestWindow Window in seconds
	 * @param string $rateLimitKey              Rate limit key
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function checkRateLimit(
		$rateLimitPrefix,
		$rateLimitMaxRequest,
		$rateLimitMaxRequestWindow,
		$rateLimitKey
	): bool {
		try {
			$result = $this->rateLimiter->check(
				prefix: $rateLimitPrefix,
				maxRequest: $rateLimitMaxRequest,
				secondsWindow: $rateLimitMaxRequestWindow,
				rateLimitKey: $rateLimitKey
			);

			if ($result['allowed']) {
				// Process the request
				return true;
			} else {
				// Return 429 Too Many request
				throw new \Exception(
					message: $result['resetOn'] - Env::$timestamp,
					code: HttpStatus::$TooManyRequest
				);
			}
		} catch (\Exception $e) {
			// Handle connection errorArr
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

		$cCidrKey = CacheServerKey::customerCidr(
			cID: $this->http->req->cID
		);
		$gCidrKey = CacheServerKey::customerGroupCidr(
			cID: $this->http->req->cID,
			gID: $this->http->req->gID
		);
		$uCidrKey = CacheServerKey::customerUserCidr(
			cID: $this->http->req->cID,
			uID: $this->http->req->uID
		);
		foreach ([$cCidrKey, $gCidrKey, $uCidrKey] as $cacheKey) {
			if (!$this->cidrChecked) {
				$this->cidrChecked = CommonFunction::checkCacheCidr(
					IP: $this->http->httpReqDetailArr['server']['httpRequestIP'],
					cidrCacheKey: $cacheKey
				);
			}
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
			|| empty($this->http->req->s['cDetail']['rateLimitMaxRequest'])
			|| empty($this->http->req->s['cDetail']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitCustomerPrefix = Env::$rateLimitCustomerPrefix;
		$rateLimitMaxRequest =
				$this->http->req->s['cDetail']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
				$this->http->req->s['cDetail']['rateLimitMaxRequestWindow'];
		$rateLimitKey = $this->http->req->cID;

		$this->rateLimitChecked = $this->checkRateLimit(
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
			|| empty($this->http->req->s['gDetail']['rateLimitMaxRequest'])
			|| empty($this->http->req->s['gDetail']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitGroupPrefix =
			Env::$rateLimitGroupPrefix;
		$rateLimitMaxRequest =
			$this->http->req->s['gDetail']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
			$this->http->req->s['gDetail']['rateLimitMaxRequestWindow'];
		$rateLimitKey = $this->http->req->cID . ':'
			. $this->http->req->uID;

		$this->rateLimitChecked = $this->checkRateLimit(
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
			|| empty($this->http->req->s['uDetail']['rateLimitMaxRequest'])
			|| empty($this->http->req->s['uDetail']['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$rateLimitUserPrefix = Env::$rateLimitUserPrefix;
		$rateLimitMaxRequest =
			$this->http->req->s['gDetail']['rateLimitMaxRequest'];
		$rateLimitMaxRequestWindow =
			$this->http->req->s['gDetail']['rateLimitMaxRequestWindow'];
		$rateLimitKey = $this->http->req->cID . ':'
			. $this->http->req->uID;

		$this->rateLimitChecked = $this->checkRateLimit(
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
		$rateLimitKey = $this->http->req->cID . ':'
			. $this->http->req->uID;

		$this->rateLimitChecked = $this->checkRateLimit(
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
		$rateLimitKey = $this->http->httpReqDetailArr['server']['httpRequestIP'];

		$this->checkRateLimit(
			rateLimitPrefix: $rateLimitIPPrefix,
			rateLimitMaxRequest: $rateLimitIPMaxRequest,
			rateLimitMaxRequestWindow: $rateLimitIPMaxRequestWindow,
			rateLimitKey: $rateLimitKey
		);
	}
}
