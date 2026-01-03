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

namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\PUT;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;

return array_merge(
    require Constants::$AUTH_QUERIES_DIR
                . DIRECTORY_SEPARATOR . 'ClientDB'
                . DIRECTORY_SEPARATOR . 'Common'
                . DIRECTORY_SEPARATOR . 'Address.php',
    [
        '__SET__' => [
            [
                'column' => 'address',
                'fetchFrom' => 'payload',
                'fetchFromValue' => 'address'
            ]
        ],
        '__WHERE__' => [
            [
                'column' => 'is_deleted',
                'fetchFrom' => 'custom',
                'fetchFromValue' => 'No'
            ],
            [
                'column' => 'id',
                'fetchFrom' => 'routeParams',
                'fetchFromValue' => 'id',
                'dataType' => DatabaseDataTypes::$PrimaryKey
            ]
        ],
    ]
);
