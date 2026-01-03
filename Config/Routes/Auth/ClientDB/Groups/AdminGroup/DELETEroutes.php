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
use Microservices\App\DatabaseDataTypes;

return [
    'registration' => [
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'DELETE' .
                DIRECTORY_SEPARATOR . 'Registration.php',
        ],
    ],
    'address' => [
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'DELETE' .
                DIRECTORY_SEPARATOR . 'Address.php',
        ],
    ],
    'registration-with-address' => [
        '{id:int}'  => [
            'dataType' => DatabaseDataTypes::$PrimaryKey,
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'DELETE' .
                DIRECTORY_SEPARATOR . 'Registration-With-Address.php',
        ],
    ],
    'category' => [
        'truncate' => [
            '__FILE__' => Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Groups' .
                DIRECTORY_SEPARATOR . 'AdminGroup' .
                DIRECTORY_SEPARATOR . 'DELETE' .
                DIRECTORY_SEPARATOR . 'Category.php',
        ]
    ]
];
