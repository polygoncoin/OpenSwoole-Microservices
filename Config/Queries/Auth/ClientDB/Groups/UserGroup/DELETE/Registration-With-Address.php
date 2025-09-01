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
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\DELETE;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => 'UPDATE `master_users` SET __SET__ WHERE __WHERE__',
    '__SET__' => [
        [
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',
            'fetchFromValue' => 'Yes'
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
            'fetchFrom' => 'uriParams',
            'fetchFromValue' => 'id',
            'dataType' => DatabaseDataTypes::$PrimaryKey
        ]
    ],
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => 'UPDATE `address` SET __SET__ WHERE __WHERE__',
            '__SET__' => [
                [
                    'column' => 'is_deleted',
                    'fetchFrom' => 'custom',
                    'fetchFromValue' => 'Yes'
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
                    'fetchFrom' => 'payload',
                    'fetchFromValue' => 'id',
                    'dataType' => DatabaseDataTypes::$PrimaryKey
                ],
                [
                    'column' => 'user_id',
                    'fetchFrom' => 'uriParams',
                    'fetchFromValue' => 'id',
                    'dataType' => DatabaseDataTypes::$PrimaryKey
                ],
            ],
        ]
    ],
    '__VALIDATE__' => [
        [
            'fn' => '_primaryKeyExist',
            'fnArgs' => [
                'table' => ['custom', 'master_users'],
                'primary' => ['custom', 'id'],
                'id' => ['uriParams', 'id']
            ],
            'errorMessage' => 'Invalid registration id'
        ],
    ],
    'useHierarchy' => true,
    'idempotentWindow' => 10
];
