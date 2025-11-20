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

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => 'UPDATE `master_users` SET __SET__ WHERE __WHERE__',
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
        [
            'column' => 'username',
            'fetchFrom' => 'payload',
            'fetchFromValue' => 'username'
        ],
        [
            'column' => 'password_hash',
            'fetchFrom' => 'function',
            'fetchFromValue' => function ($session): string {
                return password_hash(
                    password: $session['payload']['password'],
                    algo: PASSWORD_DEFAULT
                );
            }
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
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => 'UPDATE `address` SET __SET__ WHERE __WHERE__',
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
                    'fetchFrom' => 'payload',
                    'fetchFromValue' => 'id',
                    'dataType' => DatabaseDataTypes::$PrimaryKey
                ],
            ],
        ]
    ],
    '__VALIDATE__' => [
        [
            'fn' => 'primaryKeyExist',
            'fnArgs' => [
                'table' => ['custom', 'master_users'],
                'primary' => ['custom', 'id'],
                'id' => ['routeParams', 'id']
            ],
            'errorMessage' => 'Invalid registration id'
        ],
    ],
    'useHierarchy' => true,
    'idempotentWindow' => 10
];
