<?php

/**
 * HTTP Response
 * php version 8.3
 *
 * @category  HTTP_Response
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\HttpStatus;

/**
 * HTTP Response
 * php version 8.3
 *
 * @category  HTTP_Response
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpResponse
{
    /**
     * HTTP Status
     *
     * @var int
     */
    public $httpStatus;

    /**
     * JSON Encode object
     *
     * @var null|DataEncode
     */
    public $dataEncode = null;

    /**
     * Microservices Request Details
     *
     * @var array
     */
    public $http = null;

    /**
     * Constructor
     *
     * @param array $http HTTP request details
     */
    public function __construct(&$http)
    {
        $this->httpStatus = HttpStatus::$Ok;
        $this->http = &$http;
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init(): void
    {
        $this->dataEncode = new DataEncode(http: $this->http);
        $this->dataEncode->init();
    }
}
