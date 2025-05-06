<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "INSERT INTO `master_users` SET __SET__",
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
    'insertId' => 'registration:id',
    'subQuery' => [
        'address' => [
            'query' => "INSERT INTO `address` SET __SET__",
            '__SET__' => [
                'user_id' => ['insertId', 'registration:id'],
                'address' => ['payload', 'address']
            ],
            'insertId' => 'address:id',
            'payloadType' => 'Array',
            'maxPayloadObjects' => 2
        ]
    ],
    'rateLimiterMaxRequests' => 1,
    'rateLimiterSecondsWindow' => 3600,
    'useHierarchy' => true,
    'payloadType' => 'Object',
    'idempotentWindow' => 10
];
