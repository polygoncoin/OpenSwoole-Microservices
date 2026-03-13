<?php

/**
 * Http Class
 * php version 8.3
 *
 * @category  Http
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\HttpRequest;
use Microservices\App\HttpResponse;

/**
 * Http Class
 * php version 8.3
 *
 * @category  Http
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Http
{
	/**
	 * Microservices HTTP Request
	 *
	 * @var null|HttpRequest
	 */
	public $req = null;

	/**
	 * Microservices HTTP Response
	 *
	 * @var null|HttpResponse
	 */
	public $res = null;

	/**
	 * Http Request Details
	 *
	 * @var null|array
	 */
	public $iConfig = null;

	/**
	 * Initialize
	 *
	 * @param array $iConfig Http Request Details
	 *
	 * @return void
	 */
	public function init(&$iConfig): void
	{
		$this->iConfig = &$iConfig;
		$this->req = new HttpRequest(http: $this);
		$this->res = new HttpResponse(http: $this);
	}

	/**
	 * Initialize Request
	 *
	 * @return bool
	 */
	public function initRequest(): void
	{
		$this->req->init();
	}

	/**
	 * Initialize Response
	 *
	 * @return bool
	 */
	public function initResponse(): void
	{
		$this->res->init();
	}
}
