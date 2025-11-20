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

namespace Microservices\Config\Routes\Auth\ClientDB\Common\Cron;

use Microservices\App\Env;

return [
    Env::$cronRequestRoutePrefix => [
        '{cron:string}' => [
            '{id:int|!0}'  => [
                '__FILE__' => false
            ]
        ]
    ]
];
