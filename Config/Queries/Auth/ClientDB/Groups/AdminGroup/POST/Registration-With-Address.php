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
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\POST;

return [
    '__QUERY__' => 'INSERT INTO `master_users` SET __SET__',
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
        ],
        [
            'column' => 'ip',
            'fetchFrom' => 'custom',
            'fetchFromValue' => '127.0.0.1'
        ],
        [
            'column' => 'group_id',
            'fetchFrom' => 'custom',
            'fetchFromValue' => '1'
        ],
    ],
    '__INSERT-IDs__' => 'registration:id',
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => 'INSERT INTO `address` SET __SET__',
            '__SET__' => [
                [
                    'column' => 'user_id',
                    'fetchFrom' => '__INSERT-IDs__',
                    'fetchFromValue' => 'registration:id'
                ],
                [
                    'column' => 'address',
                    'fetchFrom' => 'payload',
                    'fetchFromValue' => 'address'
                ]
            ],
            '__INSERT-IDs__' => 'address:id',
        ]
    ],
    'useHierarchy' => true,
    'idempotentWindow' => 10
];
