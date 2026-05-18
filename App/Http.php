<?php

/**
 * HTTP Class
 * php version 8.3
 *
 * @category  Http
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\HttpRequest;
use Microservices\App\HttpResponse;

/**
 * HTTP Class
 * php version 8.3
 *
 * @category  Http
 * @package   Openswoole-Microservices
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
	 * HTTP request data
	 *
	 * @var null|array
	 */
	public $httpReqData = null;

	/**
	 * Constructor
	 *
	 * @param array $httpReqData
	 */
	public function __construct(&$httpReqData)
	{
		$this->httpReqData = &$httpReqData;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		$this->req = new HttpRequest(http: $this);
		$this->res = new HttpResponse(http: $this);

		if ($this->req->isPrivateRequest) {
			$this->req->ROUTES_DIR = Constant::$ROUTES_PRIVATE_DIR;
			$this->req->QUERIES_DIR = Constant::$QUERIES_PRIVATE_DIR;

			$this->res->HTML_DIR = Constant::$HTML_PRIVATE_DIR;
			$this->res->PHP_DIR = Constant::$PHP_PRIVATE_DIR;
			$this->res->XSLT_DIR = Constant::$XSLT_PRIVATE_DIR;
		} else {
			$this->req->ROUTES_DIR = Constant::$ROUTES_PUBLIC_DIR;
			$this->req->QUERIES_DIR = Constant::$QUERIES_PUBLIC_DIR;

			$this->res->HTML_DIR = Constant::$HTML_PUBLIC_DIR;
			$this->res->PHP_DIR = Constant::$PHP_PUBLIC_DIR;
			$this->res->XSLT_DIR = Constant::$XSLT_PUBLIC_DIR;
		}

		return true;
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
