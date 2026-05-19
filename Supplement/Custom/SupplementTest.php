<?php

/**
 * CustomAPI
 * php version 8.3
 *
 * @category  CustomAPI
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Custom;

use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * CustomAPI Supplement Test
 * php version 8.3
 *
 * @category  CustomAPI_SupplementTest
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class SupplementTest implements CustomInterface
{
	use CustomTrait;

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
		return $this->http->req->s['payload'];
	}

	/**
	 * Process
	 *
	 * @return mixed
	 */
	public function subProcess(): mixed
	{
		return $this->http->req->s['payload'];
	}
}
