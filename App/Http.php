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
	public $httpRequestObject = null;

	/**
	 * Microservices HTTP response
	 * 
	 * @var null|HttpResponse
	 */
	public $httpResponseObject = null;

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
	public function __construct(
		&$httpReqData
	) {
		$this->httpReqData = &$httpReqData;
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		$this->httpRequestObject = new HttpRequest(
			httpObject: $this
		);
		$this->httpResponseObject = new HttpResponse(
			httpObject: $this
		);

		if ($this->httpRequestObject->isPrivateRequest) {
			$this->httpRequestObject->routesDirectory = Constant::$ROUTES_CONFIG_PRIVATE_DIRECTORY;
			$this->httpRequestObject->sqlDirectory = Constant::$SQL_CONFIG_PRIVATE_DIRECTORY;

			$this->httpResponseObject->htmlDirectory = Constant::$HTML_PRIVATE_DIRECTORY;
			$this->httpResponseObject->phpDirectory = Constant::$PHP_PRIVATE_DIRECTORY;
			$this->httpResponseObject->xsltDirectory = Constant::$XSLT_PRIVATE_DIRECTORY;
		} else {
			$this->httpRequestObject->routesDirectory = Constant::$ROUTES_CONFIG_PUBLIC_DIRECTORY;
			$this->httpRequestObject->sqlDirectory = Constant::$SQL_CONFIG_PUBLIC_DIRECTORY;

			$this->httpResponseObject->htmlDirectory = Constant::$HTML_PUBLIC_DIRECTORY;
			$this->httpResponseObject->phpDirectory = Constant::$PHP_PUBLIC_DIRECTORY;
			$this->httpResponseObject->xsltDirectory = Constant::$XSLT_PUBLIC_DIRECTORY;
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
		$this->httpRequestObject->init();
	}

	/**
	 * Initialize response
	 * 
	 * @return bool
	 */
	public function initResponse(): void
	{
		$this->httpResponseObject->init();
	}
}
