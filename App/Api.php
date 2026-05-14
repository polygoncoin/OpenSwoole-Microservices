<?php

/**
 * Initiating API
 * php version 8.3
 *
 * @category  API
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

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
 * @package   Openswoole_Microservices
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
		$this->http->initRequest();

		return true;
	}

	/**
	 * Process
	 *
	 * @return mixed
	 */
	public function process(): mixed
	{
		if (
			$this->http->req->isAuthRequest
			&& $this->http->httpReqData['server']['httpMethod'] === Constant::$GET
		) {
			$dropboxCache = new Dropbox(httpReqData: $this->http->httpReqData, http: $this->http);
			if ($dropboxCache->init(mode: 'Closed')) {
				// File exists - Serve from Dropbox
				return $dropboxCache->process();
			}
			$dropboxCache = null;
		}

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

		if ($this->processBeforePayload()) {
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
	 * @return bool
	 */
	private function processBeforePayload(): bool
	{
		$supplementProcessed = false;

		if (
			Env::$enableRoutesRequest
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
				case (
						Env::$enableCustomRequest
						&& (Env::$customRequestRoutePrefix
							=== $this->http->req->rParser->routeElementArr[0])

					):
					$supplementApiClass = __NAMESPACE__ . '\\Custom';
					break;
				case (
						Env::$enableUploadRequest
						&& (Env::$uploadRequestRoutePrefix
							=== $this->http->req->rParser->routeElementArr[0])
					):
					$supplementApiClass = __NAMESPACE__ . '\\Upload';
					break;
				case (
						Env::$enableThirdPartyRequest
						&& (Env::$thirdPartyRequestRoutePrefix
							=== $this->http->req->rParser->routeElementArr[0])
					):
					$supplementApiClass = __NAMESPACE__ . '\\ThirdParty';
					break;
				case (
						Env::$enableDropboxRequest
						&& (Env::$dropboxRequestRoutePrefix
							=== $this->http->req->rParser->routeElementArr[0])
					):
					$supplementApiClass = __NAMESPACE__ . '\\Dropbox';
					break;
			}

			if (!empty($supplementApiClass)) {
				$supplementObj = new $supplementApiClass(http: $this->http);
				$supplementObj->init();
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
	 * Execute once done with api process function
	 *
	 * @return bool
	 */
	private function processAfterPayload(): bool
	{
		return true;
	}
}
