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

return [
    'group' => [
        '{id:int|!0}'  => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'GlobalDB' .
                DIRECTORY_SEPARATOR . 'PATCH' .
                DIRECTORY_SEPARATOR . 'groups.php',
            'approve'  => [
                '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                    DIRECTORY_SEPARATOR . 'GlobalDB' .
                    DIRECTORY_SEPARATOR . 'PATCH' .
                    DIRECTORY_SEPARATOR . 'approve' .
                    DIRECTORY_SEPARATOR . 'groups.php',
            ],
            'disable'  => [
                '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                    DIRECTORY_SEPARATOR . 'GlobalDB' .
                    DIRECTORY_SEPARATOR . 'PATCH' .
                    DIRECTORY_SEPARATOR . 'disable' .
                    DIRECTORY_SEPARATOR . 'groups.php',
            ],
            'enable'  => [
                '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                    DIRECTORY_SEPARATOR . 'GlobalDB' .
                    DIRECTORY_SEPARATOR . 'PATCH' .
                    DIRECTORY_SEPARATOR . 'enable' .
                    DIRECTORY_SEPARATOR . 'groups.php',
            ],
        ],
    ],
    'client' => [
        '{id:int|!0}'  => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'GlobalDB' .
                DIRECTORY_SEPARATOR . 'PATCH' .
                DIRECTORY_SEPARATOR . 'clients.php',
            'approve'  => [
                '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                    DIRECTORY_SEPARATOR . 'GlobalDB' .
                    DIRECTORY_SEPARATOR . 'PATCH' .
                    DIRECTORY_SEPARATOR . 'approve' .
                    DIRECTORY_SEPARATOR . 'clients.php',
            ],
            'disable'  => [
                '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                    DIRECTORY_SEPARATOR . 'GlobalDB' .
                    DIRECTORY_SEPARATOR . 'PATCH' .
                    DIRECTORY_SEPARATOR . 'disable' .
                    DIRECTORY_SEPARATOR . 'clients.php',
            ],
            'enable'  => [
                '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                    DIRECTORY_SEPARATOR . 'GlobalDB' .
                    DIRECTORY_SEPARATOR . 'PATCH' .
                    DIRECTORY_SEPARATOR . 'enable' .
                    DIRECTORY_SEPARATOR . 'clients.php',
            ],
        ],
    ],
];
