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

namespace Microservices\Config\Routes\Auth\CommonRoutes\GlobalDB;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;

return [
    'groups' => [
        '__FILE__' => Constants::$AUTH_QUERIES_DIR .
            DIRECTORY_SEPARATOR . 'GlobalDB' .
            DIRECTORY_SEPARATOR . 'GET' .
            DIRECTORY_SEPARATOR . 'groups.php',
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'GlobalDB' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'groups.php',
        ],
    ],
    'clients' => [
        '__FILE__' => Constants::$AUTH_QUERIES_DIR .
            DIRECTORY_SEPARATOR . 'GlobalDB' .
            DIRECTORY_SEPARATOR . 'GET' .
            DIRECTORY_SEPARATOR . 'clients.php',
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'GlobalDB' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'clients.php',
        ],
    ]
];
