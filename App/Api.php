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
		if (
			isset($this->http)
			&& isset($this->http->req)
			&& isset($this->http->req->rParser)
			&& isset($this->http->req->rParser->routeHook)
			&& $this->http->req->rParser->routeHook !== null
			&& is_array($this->http->req->rParser->routeHook)
		) {
			$preRouteHookArr = [];
			foreach ($this->http->req->rParser->routeHook as $element => &$hookArr) {
				if (isset($hookArr['__PRE-ROUTE-HOOKS__'])) {
					$preRouteHookConfig = $hookArr['__PRE-ROUTE-HOOKS__'];
					if (count($preRouteHookConfig) === 0) {
						continue;
					}
					for ($i = 0, $iCount = count($preRouteHookConfig); $i < $iCount; $i++) {
						if (!in_array($preRouteHookConfig[$i], $preRouteHookArr)) {
							$preRouteHookArr[] = $preRouteHookConfig[$i];
						}
					}
				}
			}
			if (count($preRouteHookArr) > 0) {
				if ($this->hook === null) {
					$this->hook = new Hook(http: $this->http);
				}
				$this->hook->triggerHook(
					hookArr: $preRouteHookArr
				);
			}
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

		if ($this->checkSupplement(Env::$cronRequestRoutePrefix)) {
			$supplementClass = 'Microservices\\Supplement\\Cron\\'
				. ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
		} elseif ($this->checkSupplement(Env::$customRequestRoutePrefix)) {
			$supplementClass = 'Microservices\\Supplement\\Custom\\'
				. ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
		} elseif ($this->checkSupplement(Env::$uploadRequestRoutePrefix)) {
			$supplementClass = 'Microservices\\Supplement\\Upload\\'
				. ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
		} elseif ($this->checkSupplement(Env::$thirdPartyRequestRoutePrefix)) {
			$supplementClass = 'Microservices\\Supplement\\ThirdParty\\'
				. ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
		} else {
			$class = null;
			switch ($this->http->httpReqData['server']['httpMethod']) {
				case Constant::$GET:
					if ($this->checkSupplement(Env::$dropboxRequestRoutePrefix)) {
						$class = 'Microservices\\Supplement\\Dropbox\\'
							. ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
					} elseif ($this->checkSupplement(Env::$routesRequestRoute)) {
						$class = __NAMESPACE__ . '\\Route';
					} else {
						$class = __NAMESPACE__ . '\\Read';
					}
					break;
				case Constant::$POST:
				case Constant::$PUT:
				case Constant::$PATCH:
				case Constant::$DELETE:
					$class = __NAMESPACE__ . '\\Write';
					break;
			}
		}

		if (isset($supplementClass)) {
			if (!empty($supplementClass)) {
				$supplementObj = new Supplement(http: $this->http);
				if ($supplementObj->init(supplementClass: $supplementClass)) {
					$return = $supplementObj->process();
				}
			}
		} else {
			if ($class !== null) {
				$api = new $class(http: $this->http);
				if ($api->init()) {
					$return = $api->process();
				}
			}
		}

		// Execute Post Route Hook
		if (
			isset($this->http)
			&& isset($this->http->req)
			&& isset($this->http->req->rParser)
			&& isset($this->http->req->rParser->routeHook)
			&& $this->http->req->rParser->routeHook !== null
			&& is_array($this->http->req->rParser->routeHook)
		) {
			$postRouteHookArr = [];
			foreach ($this->http->req->rParser->routeHook as $element => &$hookArr) {
				if (isset($hookArr['__POST-ROUTE-HOOKS__'])) {
					$postRouteHookConfig = $hookArr['__POST-ROUTE-HOOKS__'];
					if (count($postRouteHookConfig) === 0) {
						continue;
					}
					for ($i = 0, $iCount = count($postRouteHookConfig); $i < $iCount; $i++) {
						if (!in_array($postRouteHookConfig[$i], $postRouteHookArr)) {
							$postRouteHookArr[] = $postRouteHookConfig[$i];
						}
					}
				}
			}
			if (count($postRouteHookArr) > 0) {
				if ($this->hook === null) {
					$this->hook = new Hook(http: $this->http);
				}
				$this->hook->triggerHook(
					hookArr: $postRouteHookArr
				);
			}
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
