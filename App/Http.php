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
use Microservices\App\Environment;
use Microservices\App\HttpRequest;
use Microservices\App\HttpResponse;
use Microservices\App\Server\CacheServer;
use Microservices\App\Server\DatabaseServer;
use Microservices\App\SessionHandler\Session;

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
	 * HTTP request data
	 * 
	 * @var null|array
	 */
	public $httpReqData = null;

	/**
	 * Environment data
	 * 
	 * @var null|Environment
	 */
	public $Env = null;

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
	 * Session object
	 * 
	 * @var null|Session
	 */
	public $sessionObject = null;

	/**
	 * Customer Cache Object
	 * 
	 * @var null|CacheServer
	 */
	public $cacheObject = null;

	/**
	 * Customer Database Object
	 * 
	 * @var null|DatabaseServer
	 */
	public $databaseObject = null;

	/**
	 * Customer Query Cache Object
	 * 
	 * @var null|CacheServer
	 */
	public $queryCacheObject = null;

	/**
	 * Constructor
	 * 
	 * @param array $httpReqData HTTP request data
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

		return Constant::$TRUE;
	}

	/**
	 * Initialize request
	 * 
	 * @return void
	 */
	public function initRequest(): void
	{
		$this->httpRequestObject->init();
	}

	/**
	 * Initialize response
	 * 
	 * @return void
	 */
	public function initResponse(): void
	{
		$this->httpResponseObject->init();
	}
}
