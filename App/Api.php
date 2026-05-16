<?php

/**
 * Initiating API
 * php version 8.3
 *
 * @category  API
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CommonFunction;
use Microservices\App\Dropbox;
use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\Http;
use Microservices\App\Supplement;

/**
 * Class to initialize api HTTP request
 * php version 8.3
 *
 * @category  API
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Api
{
	/**
	 * Hook object
	 *
	 * @var null|Hook
	 */
	private $hook = null;

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
		return true;
	}

	/**
	 * Process
	 *
	 * @return mixed
	 */
	public function process(): mixed
	{
		// Execute Pre Route Hook
		if (isset($this->http->req->rParser->routeHook['__PRE-ROUTE-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook(http: $this->http);
			}
			$this->hook->triggerHook(
				hookConfig: $this->http->req->rParser->routeHook['__PRE-ROUTE-HOOKS__']
			);
		}

		// Load Payloads
		if (
			!in_array(
				$this->http->req->rParser->routeEndingReservedKeyword,
				[
					Env::$explainRequestRouteKeyword,
					Env::$importSampleRequestRouteKeyword
				]
			)
		) {
			$this->http->req->loadPayload();
		}

		if ($return = $this->preProcess()) {
			if (
				is_array($return)
				&& count($return) === 3
			) {
				return $return;
			}
			return true;
		}

		$class = null;
		switch ($this->http->httpReqData['server']['httpMethod']) {
			case Constant::$GET:
				$class = __NAMESPACE__ . '\\Read';
				break;
			case Constant::$POST:
			case Constant::$PUT:
			case Constant::$PATCH:
			case Constant::$DELETE:
				$class = __NAMESPACE__ . '\\Write';
				break;
		}

		if ($class !== null) {
			$api = new $class(http: $this->http);
			if ($api->init()) {
				$return = $api->process();
			}
		}

		// Check & Process Cron / ThirdParty calls
		$this->processAfterPayload();

		// Execute Post Route Hook
		if (isset($this->http->req->rParser->routeHook['__POST-ROUTE-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook(http: $this->http);
			}
			$this->hook->triggerHook(
				hookConfig: $this->http->req->rParser->routeHook['__POST-ROUTE-HOOKS__']
			);
		}

		if (
			is_array($return)
			&& count($return) === 3
		) {
			return $return;
		}

		return true;
	}

	/**
	 * Process before collecting Payload
	 *
	 * @return mixed
	 */
	private function preProcess(): mixed
	{
		$supplementProcessed = false;

		if (
			CommonFunction::isEnabled(http: $this->http, feature: 'enableRoutesRequest')
			&& Env::$routesRequestRoute === $this->http->req->rParser->routeElementArr[0]
		) {
			$supplementApiClass = __NAMESPACE__ . '\\Route';
			$supplementObj = new $supplementApiClass(http: $this->http);
			if ($supplementObj->init()) {
				$supplementObj->process();
				$supplementProcessed = true;
			}
		} else {
			$supplementApiClass = null;
			switch (true) {
				case ($this->checkSupplement(Env::$customRequestRoutePrefix)):
					$supplementApiClass = __NAMESPACE__ . '\\Custom';
					break;
				case ($this->checkSupplement(Env::$dropboxRequestRoutePrefix)):
					$supplementApiClass = __NAMESPACE__ . '\\Dropbox';
					$supplementObj = new $supplementApiClass(http: $this->http);
					if ($supplementObj->init()) {
						$return = $supplementObj->process();
						if (
							is_array($return)
							&& count($return) === 3
						) {
							return $return;
						}
						return $supplementProcessed;
					}
					break;
				case ($this->checkSupplement(Env::$uploadRequestRoutePrefix)):
					$supplementApiClass = __NAMESPACE__ . '\\Upload';
					break;
				case ($this->checkSupplement(Env::$thirdPartyRequestRoutePrefix)):
					$supplementApiClass = __NAMESPACE__ . '\\ThirdParty';
					break;
			}

			if (!empty($supplementApiClass)) {
				$supplementObj = new $supplementApiClass(http: $this->http);
				$supplement = new Supplement(http: $this->http);
				if ($supplement->init(supplementObj: $supplementObj)) {
					$supplement->process();
					$supplementProcessed = true;
				}
			}
		}

		return $supplementProcessed;
	}

	/**
	 * Process before collecting Payload
	 *
	 * @param string $supplementMode
	 *
	 * @return bool
	 */
	private function checkSupplement($supplementMode): bool
	{
		return (
			$this->http->req->rParser->routeStartingWithReservedKeywordFlag
			&& $this->http->req->rParser->routeStartingReservedKeyword === $supplementMode
		);
	}

	/**
	 * Execute once done with api process function
	 *
	 * @return bool
	 */
	private function processAfterPayload(): bool
	{
		return true;
	}
}
