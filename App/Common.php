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
     * Constructor
     *
     * @param array $http HTTP request details
     */
    public function __construct(&$http)
    {
        $this->http = &$http;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): void
    {
        $this->req = new HttpRequest(http: $this->http);
        $this->req->init();

        $this->res = new HttpResponse(http: $this->http);
        $this->res->init();
    }
}
