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
	private $http = null;

	/**
	 * Hook object
	 *
	 * @var null|HookInterface
	 */
	private $hookObj = null;

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
	 * Trigger Hook
	 *
	 * @param array $hookConfig Hook configuration
	 *
	 * @return bool
	 */
	public function triggerHook($hookConfig): bool
	{
		if (is_array(value: $hookConfig)) {
			for ($i = 0, $iCount = count(value: $hookConfig); $i < $iCount; $i++) {
				$hook = $hookConfig[$i];
				$hookFile = Constant::$WWW
					. DIRECTORY_SEPARATOR . 'Hook'
					. DIRECTORY_SEPARATOR . $hook . '.php';
				if (file_exists(filename: $hookFile)) {
					$hookClass = 'Microservices\\Hook\\' . $hook;
					$this->hookObj = new $hookClass(http: $this->http);
					if ($this->hookObj->init()) {
						$this->hookObj->process();
					}
				} else {
					throw new \Exception(
						message: "Hook '{$hook}' missing",
						code: HttpStatus::$InternalServerError
					);
				}
			}
		}
		return true;
	}
}
