<?php

/**
 * API Route config
 * php version 8.3
 *
 * @category  API_Route_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Config\Routes\Auth\ClientDB\Common\Custom;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    Env::$customRequestRoutePrefix => [
        '{custom:string}' => [
            'dataType' => DatabaseDataTypes::$Default,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR
                . DIRECTORY_SEPARATOR . 'ClientDB'
                . DIRECTORY_SEPARATOR . 'Groups'
                . DIRECTORY_SEPARATOR . 'UserGroup'
                . DIRECTORY_SEPARATOR . 'POST'
                . DIRECTORY_SEPARATOR . 'SupplementTest.php'
        ]
    ]
];
