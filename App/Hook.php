<?php

/**
 * Hook
 * php version 8.3
 * 
 * @category  Hook
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\Hook\HookInterface;

/**
 * Executes configured hooks
 * php version 8.3
 * 
 * @category  Hook
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Hook
{
	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Hook object
	 * 
	 * @var null|HookInterface
	 */
	private $hookObject = null;

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
	 * Trigger Hook
	 * 
	 * @param array $hookArray Hook configuration
	 * 
	 * @return bool
	 */
	public function triggerHook(
		$hookArray
	): bool {
		if (
			is_array(
				value: $hookArray
			)
		) {
			$indexCount = count(
				value: $hookArray
			);
			for ($index = 0; $index < $indexCount; $index++) {
				$hookName = $hookArray[$index];

				$hookFile = Constant::$WWW
					. DIRECTORY_SEPARATOR . 'Hook'
					. DIRECTORY_SEPARATOR . $hookName . '.php';

				if (
					file_exists(
						filename: $hookFile
					)
				) {
					$hookClass = 'Microservices\\Hook\\' . $hookName;
					$this->hookObject = new $hookClass(
						httpObject: $this->httpObject
					);
					if ($this->hookObject->init()) {
						$this->hookObject->process();
					}
				} else {
					throw new \Exception(
						message: "Hook '{$hookObject}' missing",
						code: HttpStatus::$InternalServerError
					);
				}
			}
		}
		return Constant::$TRUE;
	}
}
