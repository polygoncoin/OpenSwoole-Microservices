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
use Microservices\App\HttpStatus;
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
			&& is_array(value: $this->http->req->rParser->routeHook)
		) {
			$preRouteHookArr = [];
			foreach ($this->http->req->rParser->routeHook as $element => &$hookArr) {
				if (isset($hookArr['__PRE-ROUTE-HOOKS__'])) {
					$preRouteHookConfig = $hookArr['__PRE-ROUTE-HOOKS__'];
					if (count(value: $preRouteHookConfig) === 0) {
						continue;
					}
					for ($i = 0, $iCount = count(value: $preRouteHookConfig); $i < $iCount; $i++) {
						if (
							!in_array(
								needle: $preRouteHookConfig[$i],
								haystack: $preRouteHookArr,
								strict: true
							)
						) {
							$preRouteHookArr[] = $preRouteHookConfig[$i];
						}
					}
				}
			}
			if (count(value: $preRouteHookArr) > 0) {
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
				needle: $this->http->req->rParser->routeEndingReservedKeyword,
				haystack: [
					Env::$explainRequestRouteKeyword,
					Env::$importSampleRequestRouteKeyword
				],
				strict: true
			)
		) {
			$this->http->req->loadPayload();
		}

		$class = null;
		$supplementClass = null;
		if ($this->checkSupplement(Env::$cronRequestRoutePrefix)) {
			$supplementClassFileName = ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
			$supplementClassFileLocation = Constant::$WWW
					. DIRECTORY_SEPARATOR . 'Supplement'
					. DIRECTORY_SEPARATOR . 'Cron'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (file_exists(filename: $supplementClassFileLocation)) {
				$supplementClass = 'Microservices\\Supplement\\Cron\\' . $supplementClassFileName;
			}
		} elseif ($this->checkSupplement(Env::$customRequestRoutePrefix)) {
			$supplementClassFileName = ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
			$supplementClassFileLocation = Constant::$WWW
					. DIRECTORY_SEPARATOR . 'Supplement'
					. DIRECTORY_SEPARATOR . 'Custom'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (file_exists(filename: $supplementClassFileLocation)) {
				$supplementClass = 'Microservices\\Supplement\\Custom\\' . $supplementClassFileName;
			}
		} elseif ($this->checkSupplement(Env::$uploadRequestRoutePrefix)) {
			$supplementClassFileName = ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
			$supplementClassFileLocation = Constant::$WWW
					. DIRECTORY_SEPARATOR . 'Supplement'
					. DIRECTORY_SEPARATOR . 'Upload'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (file_exists(filename: $supplementClassFileLocation)) {
				$supplementClass = 'Microservices\\Supplement\\Upload\\' . $supplementClassFileName;
			}
		} elseif ($this->checkSupplement(Env::$thirdPartyRequestRoutePrefix)) {
			$supplementClassFileName = ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
			$supplementClassFileLocation = Constant::$WWW
					. DIRECTORY_SEPARATOR . 'Supplement'
					. DIRECTORY_SEPARATOR . 'ThirdParty'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (file_exists(filename: $supplementClassFileLocation)) {
				$supplementClass = 'Microservices\\Supplement\\ThirdParty\\' . $supplementClassFileName;
			}
		} else {
			switch ($this->http->httpReqData['server']['httpMethod']) {
				case Constant::$GET:
					if ($this->checkSupplement(Env::$dropboxRequestRoutePrefix)) {
						$classFileName = ucfirst(string: $this->http->req->rParser->routeElementArr[1]);
						$classFileLocation = Constant::$WWW
								. DIRECTORY_SEPARATOR . 'Supplement'
								. DIRECTORY_SEPARATOR . 'Dropbox'
								. DIRECTORY_SEPARATOR . $classFileName . '.php';

						if (file_exists(filename: $classFileLocation)) {
							$class = 'Microservices\\Supplement\\Dropbox\\' . $classFileName;
						}
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

		if ($supplementClass !== null) {
			$supplementObj = new Supplement(http: $this->http);
			if ($supplementObj->init(supplementClass: $supplementClass)) {
				$return = $supplementObj->process();
			}
		} elseif ($class !== null) {
			$api = new $class(http: $this->http);
			if ($api->init()) {
				$return = $api->process();
			}
		} else {
			throw new \Exception(
				message: 'API class file not found',
				code: HttpStatus::$NotFound
			);
		}

		// Execute Post Route Hook
		if (
			isset($this->http)
			&& isset($this->http->req)
			&& isset($this->http->req->rParser)
			&& isset($this->http->req->rParser->routeHook)
			&& $this->http->req->rParser->routeHook !== null
			&& is_array(value: $this->http->req->rParser->routeHook)
		) {
			$postRouteHookArr = [];
			foreach ($this->http->req->rParser->routeHook as $element => &$hookArr) {
				if (isset($hookArr['__POST-ROUTE-HOOKS__'])) {
					$postRouteHookConfig = $hookArr['__POST-ROUTE-HOOKS__'];
					if (count(value: $postRouteHookConfig) === 0) {
						continue;
					}
					for ($i = 0, $iCount = count(value: $postRouteHookConfig); $i < $iCount; $i++) {
						if (
							!in_array(
								needle: $postRouteHookConfig[$i],
								haystack: $postRouteHookArr,
								strict: true
							)
						) {
							$postRouteHookArr[] = $postRouteHookConfig[$i];
						}
					}
				}
			}
			if (count(value: $postRouteHookArr) > 0) {
				if ($this->hook === null) {
					$this->hook = new Hook(http: $this->http);
				}
				$this->hook->triggerHook(
					hookArr: $postRouteHookArr
				);
			}
		}

		if (
			is_array(value: $return)
			&& count(value: $return) === 3
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
