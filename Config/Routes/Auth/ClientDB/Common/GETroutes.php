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

namespace Microservices\Config\Routes\Auth\CommonRoutes\Client;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'category' => [
        '__FILE__' => Constants::$AUTH_QUERIES_DIR .
            DIRECTORY_SEPARATOR . 'ClientDB' .
            DIRECTORY_SEPARATOR . 'groups' .
            DIRECTORY_SEPARATOR . 'UserGroup' .
            DIRECTORY_SEPARATOR . 'GET' .
            DIRECTORY_SEPARATOR . 'Category-all.php',
        'search' => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'groups' .
                DIRECTORY_SEPARATOR . 'UserGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'SearchCategory.php',
        ],
        '{id:int}' => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'groups' .
                DIRECTORY_SEPARATOR . 'UserGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Category-single.php',
        ]
    ],
    'registration' => [
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'groups' .
                DIRECTORY_SEPARATOR . 'UserGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Registration-single.php',
        ],
    ],
    'address' => [
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'groups' .
                DIRECTORY_SEPARATOR . 'UserGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Address-single.php',
        ],
    ],
    'registration-with-address' => [
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'groups' .
                DIRECTORY_SEPARATOR . 'UserGroup' .
                DIRECTORY_SEPARATOR . 'GET' .
                DIRECTORY_SEPARATOR . 'Registration-With-Address-single.php',
        ],
    ],
    Env::$routesRequestRoute => [
        '__FILE__' => false,
        '{method:string}' => [
            'dataType' => DatabaseDataTypes::$HttpMethods,
            '__FILE__' => false
        ]
    ]
];
