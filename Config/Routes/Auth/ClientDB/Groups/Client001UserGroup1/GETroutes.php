<?php

/**
 * API Route config
 * php version 8.3
 *
 * @category  API_Route_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Config\Routes\Auth\ClientDB\Groups\Client001UserGroup1;

use Microservices\App\Constants;

return array_merge(
    require Constants::$AUTH_ROUTES_DIR .
        DIRECTORY_SEPARATOR . 'ClientDB' .
        DIRECTORY_SEPARATOR . 'Common' .
        DIRECTORY_SEPARATOR . 'GETroutes.php',
    require Constants::$AUTH_ROUTES_DIR .
        DIRECTORY_SEPARATOR . 'ClientDB' .
        DIRECTORY_SEPARATOR . 'Common' .
        DIRECTORY_SEPARATOR . 'Custom' .
        DIRECTORY_SEPARATOR . 'GETroutes.php',
    require Constants::$AUTH_ROUTES_DIR .
        DIRECTORY_SEPARATOR . 'ClientDB' .
        DIRECTORY_SEPARATOR . 'Common' .
        DIRECTORY_SEPARATOR . 'ThirdParty' .
        DIRECTORY_SEPARATOR . 'GETroutes.php'
);
