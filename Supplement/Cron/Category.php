<?php

/**
 * CronAPI
 * php version 8.3
 *
 * @category  CronAPI_Trait
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Cron;

use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\Supplement\Cron\CronInterface;
use Microservices\Supplement\Cron\CronTrait;

/**
 * CronAPI
 * php version 8.3
 *
 * @category  CronAPI_Example
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Category implements CronInterface
{
	use CronTrait;

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
		throw new \Exception(
			message: 'message as desired',
			code: HttpStatus::$Ok
		);

		return true;
	}
}
