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

namespace Microservices\Hook;

use Microservices\App\Constant;
use Microservices\App\Http;
use Microservices\Hook\HookInterface;
use Microservices\Hook\HookTrait;

/**
 * Hook Example class
 * php version 8.3
 * 
 * @category  Hook_Example
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Hook_Example implements HookInterface
{
	use HookTrait;

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
		$this->execHook();

		return true;
	}

	/**
	 * Exec Hook
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function execHook(): void
	{
		// Change payload.
		$this->httpObject->httpRequestObject->activeRequestData['payload']['hook'] = Constant::$YES;
	}
}
