<?php

/**
 * Initialize Cron
 * php version 8.3
 *
 * @category  Cron
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Http;
use Microservices\Supplement\Cron\CronInterface;

/**
 * Cron API
 * php version 8.3
 *
 * @category  CronAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Cron
{
	/**
	 * Cron API object
	 *
	 * @var null|CronInterface
	 */
	private $cronApi = null;

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
		$routeFileLocation = Constant::$AUTH_ROUTES_DIR
			. DIRECTORY_SEPARATOR . 'CustomerDB'
			. DIRECTORY_SEPARATOR . 'Common'
			. DIRECTORY_SEPARATOR . 'Cron'
			. DIRECTORY_SEPARATOR . $this->http->req->METHOD . 'routes.php';
		$this->http->req->rParser->parseRoute(routeFileLocation: $routeFileLocation);

		$class = 'Microservices\\Supplement\\Cron\\'
			. ucfirst(string: $this->http->req->rParser->routeElements[1]);

		$this->cronApi = new $class($this->http);

		return $this->cronApi->init();
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
		return $this->cronApi->$function($payload);
	}
}
