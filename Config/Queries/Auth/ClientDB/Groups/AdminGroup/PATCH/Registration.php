<?php

/**
 * API Query config
 * php version 8.3
 *
 * @category  API_Query_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Config\Queries\Auth\ClientDB\groups\AdminGroup\PATCH;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;

return array_merge(
    require Constants::$AUTH_QUERIES_DIR .
                DIRECTORY_SEPARATOR . 'ClientDB' .
                DIRECTORY_SEPARATOR . 'Common' .
                DIRECTORY_SEPARATOR . 'Registration.php',
    [
        '__SET__' => [
            [
                'column' => 'firstname',
                'fetchFrom' => 'payload',
                'fetchFromValue' => 'firstname'
            ],
            [
                'column' => 'lastname',
                'fetchFrom' => 'payload',
                'fetchFromValue' => 'lastname'
            ],
            [
                'column' => 'email',
                'fetchFrom' => 'payload',
                'fetchFromValue' => 'email'
            ],
        ],
        '__WHERE__' => [
            [
                'column' => 'id',
                'fetchFrom' => 'routeParams',
                'fetchFromValue' => 'id',
                'dataType' => DatabaseDataTypes::$PrimaryKey
            ]
        ],
    ]
);
