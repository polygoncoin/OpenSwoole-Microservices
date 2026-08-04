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

use Microservices\App\Constant;
use Microservices\App\Dropbox;
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
	private $hookObject = null;

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
			isset($this->httpObject)
			&& isset($this->httpObject->httpRequestObject)
			&& isset($this->httpObject->httpRequestObject->routeParserObject)
			&& isset($this->httpObject->httpRequestObject->routeParserObject->routeHook)
			&& $this->httpObject->httpRequestObject->routeParserObject->routeHook !== Constant::$NULL
			&& is_array(
				value: $this->httpObject->httpRequestObject->routeParserObject->routeHook
			)
		) {
			$preRouteHookArray = [];
			foreach ($this->httpObject->httpRequestObject->routeParserObject->routeHook as $element => &$hookArray) {
				if (isset($hookArray['__PRE-ROUTE-HOOK__'])) {
					$preRouteHookConfig = $hookArray['__PRE-ROUTE-HOOK__'];
					if (
						count(
							value: $preRouteHookConfig
						) === 0
					) {
						continue;
					}

					 $indexCount = count(
						value: $preRouteHookConfig
					 );
					for ($index = 0; $index < $indexCount; $index++) {
						if (
							!in_array(
								needle: $preRouteHookConfig[$index],
								haystack: $preRouteHookArray,
								strict: Constant::$TRUE
							)
						) {
							$preRouteHookArray[] = $preRouteHookConfig[$index];
						}
					}
				}
			}
			if (
				count(
					value: $preRouteHookArray
				) > 0
			) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $preRouteHookArray
				);
			}
		}

		// Load Payloads
		if (
			!in_array(
				needle: $this->httpObject->httpRequestObject->routeParserObject->routeEndingReservedKeyword,
				haystack: [
					Env::$explainRequestRouteKeyword,
					Env::$importSampleRequestRouteKeyword
				],
				strict: Constant::$TRUE
			)
		) {
			$this->httpObject->httpRequestObject->loadPayload();
		}

		$class = null;
		$supplementClass = null;
		if (
			$this->checkSupplement(
				Env::$cronRequestRoutePrefix
			)
		) {
			$supplementClassFileName = ucfirst(
				string: $this->httpObject->httpRequestObject->routeParserObject->routeElementArray[1]
			);
			$supplementClassFileLocation = Constant::$SUPPLEMENT_DIRECTORY
					. DIRECTORY_SEPARATOR . 'Cron'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (
				file_exists(
					filename: $supplementClassFileLocation
				)
			) {
				$supplementClass = Constant::$SUPPLEMENT_NS . '\\Cron\\' . $supplementClassFileName;
			}
		} elseif (
			$this->checkSupplement(
				Env::$customRequestRoutePrefix
			)
		) {
			$supplementClassFileName = ucfirst(
				string: $this->httpObject->httpRequestObject->routeParserObject->routeElementArray[1]
			);
			$supplementClassFileLocation = Constant::$SUPPLEMENT_DIRECTORY
					. DIRECTORY_SEPARATOR . 'Custom'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (
				file_exists(
					filename: $supplementClassFileLocation
				)
			) {
				$supplementClass = Constant::$SUPPLEMENT_NS . '\\Custom\\' . $supplementClassFileName;
			}
		} elseif (
			$this->checkSupplement(
				Env::$uploadRequestRoutePrefix
			)
		) {
			$supplementClassFileName = ucfirst(
				string: $this->httpObject->httpRequestObject->routeParserObject->routeElementArray[1]
			);
			$supplementClassFileLocation = Constant::$SUPPLEMENT_DIRECTORY
					. DIRECTORY_SEPARATOR . 'Upload'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (
				file_exists(
					filename: $supplementClassFileLocation
				)
			) {
				$supplementClass = Constant::$SUPPLEMENT_NS . '\\Upload\\' . $supplementClassFileName;
			}
		} elseif (
			$this->checkSupplement(
				Env::$thirdPartyRequestRoutePrefix
			)
		) {
			$supplementClassFileName = ucfirst(
				string: $this->httpObject->httpRequestObject->routeParserObject->routeElementArray[1]
			);
			$supplementClassFileLocation = Constant::$SUPPLEMENT_DIRECTORY
					. DIRECTORY_SEPARATOR . 'ThirdParty'
					. DIRECTORY_SEPARATOR . $supplementClassFileName . '.php';

			if (
				file_exists(
					filename: $supplementClassFileLocation
				)
			) {
				$supplementClass = Constant::$SUPPLEMENT_NS . '\\ThirdParty\\' . $supplementClassFileName;
			}
		} else {
			switch ($this->httpObject->httpReqData['server']['httpRequestMethod']) {
				case Constant::$GET:
				case Constant::$QUERY:
					if (
						$this->checkSupplement(
							Env::$dropboxRequestRoutePrefix
						)
					) {
						$classFileName = ucfirst(
							string: $this->httpObject->httpRequestObject->routeParserObject->routeElementArray[1]
						);
						$classFileLocation = Constant::$SUPPLEMENT_DIRECTORY
								. DIRECTORY_SEPARATOR . 'Dropbox'
								. DIRECTORY_SEPARATOR . $classFileName . '.php';

						if (
							file_exists(
								filename: $classFileLocation
							)
						) {
							$class = Constant::$SUPPLEMENT_NS . '\\Dropbox\\' . $classFileName;
						}
					} elseif (
						$this->checkSupplement(
							Env::$routesRequestRoute
						)
					) {
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

		if ($supplementClass !== Constant::$NULL) {
			$supplementObject = new Supplement(
				httpObject: $this->httpObject
			);
			if (
				$supplementObject->init(
					supplementClass: $supplementClass
				)
			) {
				$return = $supplementObject->process();
			}
		} elseif ($class !== Constant::$NULL) {
			$api = new $class(
				httpObject: $this->httpObject
			);
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
			isset($this->httpObject)
			&& isset($this->httpObject->httpRequestObject)
			&& isset($this->httpObject->httpRequestObject->routeParserObject)
			&& isset($this->httpObject->httpRequestObject->routeParserObject->routeHook)
			&& $this->httpObject->httpRequestObject->routeParserObject->routeHook !== Constant::$NULL
			&& is_array(
				value: $this->httpObject->httpRequestObject->routeParserObject->routeHook
			)
		) {
			$postRouteHookArray = [];
			foreach ($this->httpObject->httpRequestObject->routeParserObject->routeHook as $element => &$hookArray) {
				if (isset($hookArray['__POST-ROUTE-HOOK__'])) {
					$postRouteHookConfig = $hookArray['__POST-ROUTE-HOOK__'];
					if (
						count(
							value: $postRouteHookConfig
						) === 0
					) {
						continue;
					}

					$indexCount = count(
						value: $postRouteHookConfig
					);
					for ($index = 0; $index < $indexCount; $index++) {
						if (
							!in_array(
								needle: $postRouteHookConfig[$index],
								haystack: $postRouteHookArray,
								strict: Constant::$TRUE
							)
						) {
							$postRouteHookArray[] = $postRouteHookConfig[$index];
						}
					}
				}
			}
			if (
				count(
					value: $postRouteHookArray
				) > 0
			) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $postRouteHookArray
				);
			}
		}

		if (
			is_array(
				value: $return
			)
			&& count(
				value: $return
			) === 3
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
	private function checkSupplement(
		$supplementMode
	): bool {
		return (
			$this->httpObject->httpRequestObject->routeParserObject->routeStartingWithReservedKeywordFlag
			&& $this->httpObject->httpRequestObject->routeParserObject->routeStartingReservedKeyword === $supplementMode
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
