<?php

/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Rate Limiter
 * php version 8.3
 *
 * @category  RateLimiter
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class RateLimiter
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
	 * Check rate limit is valid
	 * 
	 * @param null|array $rateLimitDetail Rate Limit detail
	 * 
	 * @return array
	 */
	public function check(
		$rateLimitDetail
	): array {
		if (
			!$this->httpObject->Env->ENABLE_RATE_LIMITING
			|| $rateLimitDetail === null
		) {
			return [];
		}

		if ($this->httpObject->cache === Constant::$NULL) {
			throw new \Exception(
				message: 'Rate Limiter: Cache object not initialized.',
				code: HttpStatus::$InternalServerError
			);
		}

		$rateLimitKey = $rateLimitDetail['rateLimitKey'];
		$rateLimitRequestCount = (int)$rateLimitDetail['rateLimitRequestCount'];
		$rateLimitRequestWindow = (int)$rateLimitDetail['rateLimitRequestWindow'];

		$remainder = Env::$timestamp % $rateLimitRequestWindow;
		$remainder = $remainder !== 0 ? $remainder : $rateLimitRequestWindow;

		if (
			$this->httpObject->cache->cacheExist(
				cacheKey: $rateLimitKey
			)
		) {
			$requestCount = (int)$this->httpObject->cache->cacheGet(
				cacheKey: $rateLimitKey
			);
		} else {
			$requestCount = 0;
			$this->httpObject->cache->cacheSet(
				cacheKey: $rateLimitKey,
				cacheValue: $requestCount,
				cacheExpire: $remainder
			);
		}
		$requestCount++;

		$allowed = $requestCount <= $rateLimitRequestCount;
		$remaining = max(
			0,
			$rateLimitRequestCount - $requestCount
		);
		$resetOn = Env::$timestamp + $remainder;

		if ($allowed) {
			$this->httpObject->cache->cacheIncrement(
				cacheKey: $rateLimitKey
			);
		}

		return [
			'allowed' => $allowed,
			'remaining' => $remaining,
			'resetOn' => $resetOn
		];
	}

	/**
	 * Rate limit feature
	 * 
	 * @param string $feature Feature
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function rateLimitFeature(
		$feature
	): void {
		$rateLimitDetail = $this->getRateLimitDetail(
			feature: $feature
		);
		if ($rateLimitDetail === null) {
			return;
		}
		try {
			$result = $this->check(
				rateLimitDetail: $rateLimitDetail
			);

			if ($result['allowed']) {
				// Process the request
				return;
			} else {
				// Return Too Many request
				throw new \Exception(
					message: $result['resetOn'] - Env::$timestamp,
					code: HttpStatus::$TooManyRequest
				);
			}
		} catch (\Exception $e) {
			// Handle connection errorArray
			throw new \Exception(
				message: $e->getMessage(),
				code: $e->getCode()
			);
		}
	}

	/**
	 * Get Rate Limit detail
	 * 
	 * @param string $feature Feature
	 * 
	 * @return null|array
	 */
	public function getRateLimitDetail(
		$feature
	): null|array {
        $rateLimitDetail = null;

        $enableKey = "ENABLE_RATE_LIMITING_{$feature}";
        
        if (!$this->httpObject->Env->$enableKey) {
            return $rateLimitDetail;
        }

        $httpRequestIp = &$this->httpObject->httpReqData['server']['httpRequestIp'];
        $httpRequestMethod = &$this->httpObject->httpReqData['server']['httpRequestMethod'];
        $route = &$this->httpObject->httpReqData['get'][ROUTE_URL_PARAM];
		$customerData = &$this->httpObject->httpReqData['active']['customerData'];

		$customerData = &$this->httpObject->httpReqData['active']['customerData'];
		$customerUserData = &$this->httpObject->httpReqData['active']['customerUserData'];
		$customerUserGroupData = &$this->httpObject->httpReqData['active']['customerUserGroupData'];
		
		$sqlConfigData = null;
		if (
			isset($this->httpObject->httpReqData['active']['sqlData']['Config']['rateLimitRequestCount'])
			&& isset($this->httpObject->httpReqData['active']['sqlData']['Config']['rateLimitRequestWindow'])
		) {
			$sqlConfigData = [
				'rateLimitRequestCount' => $this->httpObject->httpReqData['active']['sqlData']['Config']['rateLimitRequestCount'],
				'rateLimitRequestWindow' => $this->httpObject->httpReqData['active']['sqlData']['Config']['rateLimitRequestWindow']
			];
		}

        $customerId = &$this->httpObject->httpReqData['active']['customerId'];
        $customerUserId = &$this->httpObject->httpReqData['active']['customerUserId'];
        $customerUserGroupId = &$this->httpObject->httpReqData['active']['customerUserGroupId'];

        switch ($feature) {
            case 'CUSTOMER':
                $rateLimitKey = "RL:C:{$customerId}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerData['customer_limit_count'],
					'rateLimitRequestWindow' => $customerData['customer_limit_window']
				];
                break;
            case 'CUSTOMER_IP':
                $rateLimitKey = "RL:C:{$customerId}:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerData['customer_limit_ip_count'],
					'rateLimitRequestWindow' => $customerData['customer_limit_ip_window']
				];
                break;
            case 'ROUTE' && $sqlConfigData !== null:
                $rateLimitKey = "RL:C:{$customerId}:R:{$route}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $sqlConfigData['rateLimitRequestCount'],
					'rateLimitRequestWindow' => $sqlConfigData['rateLimitRequestWindow'],
				];
                break;
            case 'ROUTE_IP' && $sqlConfigData !== null:
                $rateLimitKey = "RL:C:{$customerId}:R:{$route}:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $sqlConfigData['rateLimitRequestCount'],
					'rateLimitRequestWindow' => $sqlConfigData['rateLimitRequestWindow'],
				];
                break;
            case 'GROUP':
                $rateLimitKey = "RL:C:{$customerId}:G:{$customerUserGroupId}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserGroupData['customer_user_group_limit_count'],
					'rateLimitRequestWindow' => $customerUserGroupData['customer_user_group_limit_window']
				];
                break;
            case 'GROUP_IP':
                $rateLimitKey = "RL:C:{$customerId}:G:{$customerUserGroupId}:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserGroupData['customer_user_group_limit_ip_count'],
					'rateLimitRequestWindow' => $customerUserGroupData['customer_user_group_limit_ip_window']
				];
                break;
            case 'GROUP_ROUTE' && $sqlConfigData !== null:
                $rateLimitKey = "RL:C:{$customerId}:G:{$customerUserGroupId}:R:{$route}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserGroupData['customer_user_group_limit_route_count'],
					'rateLimitRequestWindow' => $customerUserGroupData['customer_user_group_limit_route_window']
				];
                break;
            case 'GROUP_ROUTE_IP' && $sqlConfigData !== null:
                $rateLimitKey = "RL:C:{$customerId}:G:{$customerUserGroupId}::R:{$route}:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserGroupData['customer_user_group_limit_route_ip_count'],
					'rateLimitRequestWindow' => $customerUserGroupData['customer_user_group_limit_route_ip_window']
				];
                break;
            case 'USER':
                $rateLimitKey = "RL:C:{$customerId}:U:{$customerUserId}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserData['customer_user_limit_count'],
					'rateLimitRequestWindow' => $customerUserData['customer_user_limit_window']
				];
                break;
            case 'USER_IP':
                $rateLimitKey = "RL:C:{$customerId}:U:{$customerUserId}:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserData['customer_user_limit_ip_count'],
					'rateLimitRequestWindow' => $customerUserData['customer_user_limit_ip_window']
				];
                break;
            case 'USER_ROUTE' && $sqlConfigData !== null:
                $rateLimitKey = "RL:C:{$customerId}:U:{$customerUserId}:R:{$route}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserData['customer_user_limit_route_count'],
					'rateLimitRequestWindow' => $customerUserData['customer_user_limit_route_window']
				];
                break;
            case 'USER_ROUTE_IP' && $sqlConfigData !== null:
                $rateLimitKey = "RL:C:{$customerId}:U:{$customerUserId}:R:{$route}:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerUserData['customer_user_limit_route_ip_count'],
					'rateLimitRequestWindow' => $customerUserData['customer_user_limit_route_ip_window']
				];
                break;
            case 'LOGIN':
                $rateLimitKey = "RL:C:{$customerId}:L";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerData['customer_limit_login_count'],
					'rateLimitRequestWindow' => $customerData['customer_limit_login_window']
				];
                break;
            case 'LOGIN_IP':
                $rateLimitKey = "RL:C:{$customerId}:L:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerData['customer_limit_login_ip_count'],
					'rateLimitRequestWindow' => $customerData['customer_limit_login_ip_window']
				];
                break;
            case 'EACH_USER_LOGIN':
                $rateLimitKey = "RL:C:{$customerId}:L:U:{$customerUserId}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerData['customer_limit_each_user_login_count'],
					'rateLimitRequestWindow' => $customerData['customer_limit_each_user_login_window']
				];
                break;
            case 'EACH_USER_LOGIN_IP':
                $rateLimitKey = "RL:C:{$customerId}:L:U:{$customerUserId}:IP:{$httpRequestIp}";
				$rateLimitDetail = [
					'rateLimitKey' => $rateLimitKey,
					'rateLimitRequestCount' => $customerData['customer_limit_each_user_login_ip_count'],
					'rateLimitRequestWindow' => $customerData['customer_limit_each_user_login_ip_window']
				];
                break;
        }

        return $rateLimitDetail;
    }
}
