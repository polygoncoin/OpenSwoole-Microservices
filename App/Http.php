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
use Microservices\App\RateLimiter;
use Microservices\App\Server\CacheServer;
use Microservices\App\Server\DatabaseServer;
use Microservices\App\Server\QueryCacheServer;
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
	public $request = null;

	/**
	 * Microservices HTTP response
	 * 
	 * @var null|HttpResponse
	 */
	public $response = null;

	/**
	 * Session object
	 * 
	 * @var null|Session
	 */
	public $webSession = null;

	/**
	 * Customer Cache Object
	 * 
	 * @var null|CacheServer
	 */
	public $cache = null;

	/**
	 * Customer Database Object
	 * 
	 * @var null|DatabaseServer
	 */
	public $db = null;

	/**
	 * Customer Query Cache Object
	 * 
	 * @var null|QueryCacheServer
	 */
	public $queryCache = null;

	/**
	 * Rate Limiter
	 * 
	 * @var null|RateLimiter
	 */
	public $rateLimiter = null;

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
		$this->request = new HttpRequest(
			httpObject: $this
		);
		$this->response = new HttpResponse(
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
		$this->request->init();
	}

	/**
	 * Initialize response
	 * 
	 * @return void
	 */
	public function initResponse(): void
	{
		$this->response->init();
	}
}
