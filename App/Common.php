<?php

/**
 * Common
 * php version 8.3
 *
 * @category  Common
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\HttpRequest;
use Microservices\App\HttpResponse;

/**
 * Common Class
 * php version 8.3
 *
 * @category  Common
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Common
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
     * Microservices Request Details
     *
     * @var null|array
     */
    public $http = null;

    /**
     * Initialize
     *
     * @param array $http HTTP request details
     *
     * @return void
     */
    public function init(&$http): void
    {
        $this->http = &$http;
        $this->req = new HttpRequest(http: $this->http);
        $this->res = new HttpResponse(http: $this->http);
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
