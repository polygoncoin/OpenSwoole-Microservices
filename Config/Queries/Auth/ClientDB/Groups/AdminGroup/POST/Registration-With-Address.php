<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `master_users` SET __SET__",
    '__SET__' => [
        ['column' => 'firstname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'firstname'],
        ['column' => 'lastname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'lastname'],
        ['column' => 'email', 'fetchFrom' => 'payload', 'fetchFromValue' => 'email'],
        ['column' => 'username', 'fetchFrom' => 'payload', 'fetchFromValue' => 'username'],
        ['column' => 'password_hash', 'fetchFrom' => 'function', 'fetchFromValue' => function($session) {
            return password_hash($session['payload']['password'], PASSWORD_DEFAULT);
        }],
        ['column' => 'ip', 'fetchFrom' => 'custom', 'fetchFromValue' => '127.0.0.1'],
        ['column' => 'group_id', 'fetchFrom' => 'custom', 'fetchFromValue' => '1'],
    ],
    '__INSERT-IDs__' => 'registration:id',
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "INSERT INTO `address` SET __SET__",
            '__SET__' => [
                ['column' => 'user_id', 'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'registration:id'],
                ['column' => 'address', 'fetchFrom' => 'payload', 'fetchFromValue' => 'address']
            ],
            '__INSERT-IDs__' => 'address:id',
        ]
    ],
    'useHierarchy' => true,
    'idempotentWindow' => 10
];
