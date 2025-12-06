<?php

/**
 * Initialize Cron
 * php version 8.3
 *
 * @category  Cron
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\Supplement\Cron\CronInterface;

/**
 * Cron API
 * php version 8.3
 *
 * @category  CronAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Cron
{
    /**
     * Cron API object
     *
     * @var null|CronInterface
     */
    private $cronApi = null;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $routeFileLocation = Constants::$AUTH_ROUTES_DIR .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Common' .
            DIRECTORY_SEPARATOR . 'Cron' .
            DIRECTORY_SEPARATOR . Common::$req->METHOD . 'routes.php';
        Common::$req->rParser->parseRoute(routeFileLocation: $routeFileLocation);

        $class = 'Microservices\\Supplement\\Cron\\' .
            ucfirst(string: Common::$req->rParser->routeElements[1]);

        $this->cronApi = new $class();

        return $this->cronApi->init();
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
        return $this->cronApi->$function($payload);
    }
}
