<?php

/**
 * HTTP Class
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
 * HTTP Class
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
	 * Microservices HTTP request
	 *
	 * @var null|HttpRequest
	 */
	public $req = null;

	/**
	 * Microservices HTTP response
	 *
	 * @var null|HttpResponse
	 */
	public $res = null;

	/**
	 * HTTP request detail
	 *
	 * @var null|array
	 */
	public $httpReqDetailArr = null;

	/**
	 * Constructor
	 *
	 * @param array $httpReqDetailArr
	 */
	public function __construct(&$httpReqDetailArr)
	{
		$this->httpReqDetailArr = &$httpReqDetailArr;
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function init(): void
	{
		$this->req = new HttpRequest(http: $this);
		$this->res = new HttpResponse(http: $this);
	}

	/**
	 * Initialize request
	 *
	 * @return bool
	 */
	public function initRequest(): void
	{
		$this->req->init();
	}

	/**
	 * Initialize response
	 *
	 * @return bool
	 */
	public function initResponse(): void
	{
		$this->res->init();
	}
}
