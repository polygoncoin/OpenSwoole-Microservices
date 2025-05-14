<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `master_users` SET __SET__",
    '__SET__' => [
        'firstname' => ['payload', 'firstname'],
        'lastname' => ['payload', 'lastname'],
        'email' => ['payload', 'email'],
        'username' => ['payload', 'username'],
        'password_hash' => ['function', function($session) {
            return password_hash($session['payload']['password'], PASSWORD_DEFAULT);
        }],
        'ip' => ['custom', '127.0.0.1'],
        'group_id' => ['custom', '1'],
    ],
    '__INSERT-IDs__' => 'registration:id',
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "INSERT INTO `address` SET __SET__",
            '__SET__' => [
                'user_id' => ['__INSERT-IDs__', 'registration:id'],
                'address' => ['payload', 'address']
            ],
            '__INSERT-IDs__' => 'address:id',
        ]
    ],
    'useHierarchy' => true,
    'idempotentWindow' => 10
];
