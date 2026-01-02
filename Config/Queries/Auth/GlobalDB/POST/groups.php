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

namespace Microservices\Config\Queries\Auth\GlobalDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `{$Env::$groupsTable}` SET __SET__",
    '__SET__' => [
        [
            'column' => 'name',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'name'
        ],
        [
            'column' => 'client_id',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'client_id',
            'dataType' => DatabaseDataTypes::$INT
        ],
        [
            'column' => 'connection_id',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'connection_id',
            'dataType' => DatabaseDataTypes::$INT
        ],
        [
            'column' => 'allowed_cidrs',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'allowed_cidrs'
        ],
        [
            'column' => 'comments',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'comments'
        ],
        [
            'column' => 'created_by',
            'fetchFrom' => 'uDetails',
            'fetchFromValue' => 'id'
        ],
        [
            'column' => 'created_on',
            'fetchFrom' => 'custom',
            'fetchFromValue' => date(format: 'Y-m-d H:i:s')
        ],
        [
            'column' => 'is_approved',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ],
        [
            'column' => 'is_disabled',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ],
        [
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'No'
        ]
    ],
    '__INSERT-IDs__' => 'group:id',
];
