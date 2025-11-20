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

namespace Microservices\Config\Routes\Auth\ClientDB\Groups\AdminGroup;

use Microservices\App\Constants;
use Microservices\App\Env;

return [
    'category' => [
        '__FILE__' => Constants::$AUTH_QUERIES_DIR .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'AdminGroup' .
            DIRECTORY_SEPARATOR . 'GET' .
            DIRECTORY_SEPARATOR . 'Category-all.php',
        'search' => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Category-search.php',
        ],
        '{id:int|!0}' => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Category-Single.php',
        ]
    ],
    'registration' => [
        '__FILE__' => Constants::$AUTH_QUERIES_DIR .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'AdminGroup' .
            DIRECTORY_SEPARATOR . 'GET' .
            DIRECTORY_SEPARATOR . 'Registration-all.php',
        '{id:int|!0}'  => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Registration-single.php',
        ],
    ],
    'address' => [
        '__FILE__' => Constants::$AUTH_QUERIES_DIR .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'AdminGroup' .
            DIRECTORY_SEPARATOR . 'GET' .
            DIRECTORY_SEPARATOR . 'Address-all.php',
        '{id:int|!0}'  => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Address-single.php',
        ],
    ],
    'registration-with-address' => [
        '__FILE__' => Constants::$AUTH_QUERIES_DIR .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'Groups' .
            DIRECTORY_SEPARATOR . 'AdminGroup' .
            DIRECTORY_SEPARATOR . 'GET' .
            DIRECTORY_SEPARATOR . 'Registration-With-Address-all.php',
        '{id:int|!0}'  => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Registration-With-Address-single.php',
        ],
    ],
    Env::$routesRequestRoute => [
        '__FILE__' => false,
        '{method:string|GET, POST, PUT, PATCH, DELETE}' => [
            '__FILE__' => false
        ]
    ]
];

