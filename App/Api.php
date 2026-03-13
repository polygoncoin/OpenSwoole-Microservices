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
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Hook;
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
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		$this->api->initRequest();

		return true;
	}

	/**
	 * Process
	 *
	 * @return mixed
	 */
	public function process(): mixed
	{
		if ($this->api->req->METHOD === Constant::$GET) {
			$dropboxCache = new Dropbox(http: $this->api->http, api: $this->api);
			if ($dropboxCache->init(mode: 'Closed')) {
				// File exists - Serve from Dropbox
				return $dropboxCache->process();
			}
			$dropboxCache = null;
		}

		// Execute Pre Route Hook
		if (isset($this->api->req->rParser->routeHook['__PRE-ROUTE-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook($this->api);
			}
			$this->hook->triggerHook(
				hookConfig: $this->api->req->rParser->routeHook['__PRE-ROUTE-HOOKS__']
			);
		}

		// Load Payloads
		if (
			!in_array(
				$this->api->req->rParser->routeEndingReservedKeyword,
				[
					Env::$configRequestRouteKeyword,
					Env::$importSampleRequestRouteKeyword
				]
			)
		) {
			$this->api->req->loadPayload();
		}

		if ($this->processBeforePayload()) {
			return true;
		}

		$class = null;
		switch ($this->api->req->METHOD) {
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
			$api = new $class($this->api);
			if ($api->init()) {
				$return = $api->process();
				if (
					is_array($return)
					&& count($return) === 3
				) {
					return $return;
				}
			}
		}

		// Check & Process Cron / ThirdParty calls
		$this->processAfterPayload();

		// Execute Post Route Hook
		if (isset($this->api->req->rParser->routeHook['__POST-ROUTE-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook($this->api);
			}
			$this->hook->triggerHook(
				hookConfig: $this->api->req->rParser->routeHook['__POST-ROUTE-HOOKS__']
			);
		}

		return true;
	}

	/**
	 * Miscellaneous Functionality Before Collecting Payload
	 *
	 * @return bool
	 */
	private function processBeforePayload(): bool
	{
		$supplementProcessed = false;

		if (
			Env::$enableRoutesRequest
			&& Env::$routesRequestRoute === $this->api->req->rParser->routeElements[0]
		) {
			$supplementApiClass = __NAMESPACE__ . '\\Route';
			$supplementObj = new $supplementApiClass($this->api);
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
							=== $this->api->req->rParser->routeElements[0])

					):
					$supplementApiClass = __NAMESPACE__ . '\\Custom';
					break;
				case (
						Env::$enableUploadRequest
						&& (Env::$uploadRequestRoutePrefix
							=== $this->api->req->rParser->routeElements[0])
					):
					$supplementApiClass = __NAMESPACE__ . '\\Upload';
					break;
				case (
						Env::$enableThirdPartyRequest
						&& (Env::$thirdPartyRequestRoutePrefix
							=== $this->api->req->rParser->routeElements[0])
					):
					$supplementApiClass = __NAMESPACE__ . '\\ThirdParty';
					break;
				case (
						Env::$enableDropboxRequest
						&& (Env::$dropboxRequestRoutePrefix
							=== $this->api->req->rParser->routeElements[0])
					):
					$supplementApiClass = __NAMESPACE__ . '\\Dropbox';
					break;
			}

			if (!empty($supplementApiClass)) {
				$supplementObj = new $supplementApiClass($this->api);
				$supplementObj->init();
				$supplement = new Supplement($this->api);
				if ($supplement->init(supplementObj: $supplementObj)) {
					$supplement->process();
					$supplementProcessed = true;
				}
			}
		}

		return $supplementProcessed;
	}

	/**
	 * Miscellaneous Functionality After Collecting Payload
	 *
	 * @return bool
	 */
	private function processAfterPayload(): bool
	{
		return true;
	}
}
