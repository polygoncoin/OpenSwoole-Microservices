<?php
namespace Microservices\Config\Queries\ClientDB\POST;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "INSERT INTO `registration` SET __SET__",
    '__CONFIG__' => [
        ['payload', 'firstname', DatabaseDataTypes::$Default, Constants::$REQUIRED],
        ['payload', 'lastname', DatabaseDataTypes::$Default, Constants::$REQUIRED],
        ['payload', 'email', DatabaseDataTypes::$Default, Constants::$REQUIRED]
    ],
    '__SET__' => [
        'firstname' => ['payload', 'firstname'],
        'lastname' => ['payload', 'lastname'],
        'email' => ['payload', 'email']
    ],
    'insertId' => 'registration:id',
    'subQuery' => [
        'address' => [
            'query' => "INSERT INTO `address` SET __SET__",
            '__CONFIG__' => [
                ['payload', 'address', DatabaseDataTypes::$Default, Constants::$REQUIRED]
            ],
            '__SET__' => [
                'registration_id' => ['insertIdParams', 'registration:id'],
                'address' => ['payload', 'address']
            ],
            'insertId' => 'address:id',
        ]
    ]
];
