<?php

/**
 * Initialize Custom API
 * php version 8.3
 *
 * @category  Custom
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Http;
use Microservices\Supplement\Custom\CustomInterface;

/**
 * Custom API
 * php version 8.3
 *
 * @category  CustomAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Custom
{
	/**
	 * Custom API object
	 *
	 * @var null|CustomInterface
	 */
	private $customApi = null;

	/**
	 * Http Object
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
		$class = 'Microservices\\Supplement\\Custom\\'
			. ucfirst(string: $this->http->req->rParser->routeElements[1]);

		$this->customApi = new $class($this->http);

		return $this->customApi->init();
	}

	/**
	 * Process
	 *
	 * @param string $function Function
	 * @param array  $payload  Payload
	 *
	 * @return array
	 */
	public function process($function, $payload): array
	{
		return $this->customApi->$function($payload);
	}
}
