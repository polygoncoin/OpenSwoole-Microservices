<?php

/**
 * Initialize Upload
 * php version 8.3
 *
 * @category  Upload
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Common;
use Microservices\Supplement\Upload\UploadInterface;

/**
 * Cron API
 * php version 8.3
 *
 * @category  UploadAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Upload
{
    /**
     * Upload API object
     *
     * @var null|UploadInterface
     */
    private $api = null;

    /**
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $class = 'Microservices\\Supplement\\Upload\\' .
            ucfirst(string: $this->c->req->rParser->routeElements[1]);

        $this->api = new $class(common: $this->c);

        return $this->api->init();
    }

    /**
     * Process
     *
     * @param string $function Function
     * @param array  $payload  Payload
     *
     * @return array
     */
    public function process($function, $payload): array
    {
        return $this->api->$function($payload);
    }
}
