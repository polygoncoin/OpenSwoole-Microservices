<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "INSERT INTO `registration` SET __SET__",
    '__SET__' => [
        'firstname' => ['payload', 'firstname'],
        'lastname' => ['payload', 'lastname'],
        'email' => ['payload', 'email']
    ],
    'insertId' => 'registration:id',
    'subQuery' => [
        'address' => [
            'query' => "INSERT INTO `address` SET __SET__",
            '__SET__' => [
                'registration_id' => ['insertIdParams', 'registration:id'],
                'address' => ['payload', 'address']
            ],
            'insertId' => 'address:id',
        ]
    ],
    "idempotentWindow" => 10
];
