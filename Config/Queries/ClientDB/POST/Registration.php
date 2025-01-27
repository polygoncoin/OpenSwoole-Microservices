<?php
namespace Microservices\Config\Queries\ClientDB\POST;

return [
    'query' => "INSERT INTO `registration` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['payload', 'firstname', $Constants::$REQUIRED],
        ['payload', 'lastname', $Constants::$REQUIRED],
        ['payload', 'email', $Constants::$REQUIRED]
    ],
    '__SET__' => [
        //column => [payload|userInfo|uriParams|insertIdParams|{custom}, key|{value}],
        'firstname' => ['payload', 'firstname'],
        'lastname' => ['payload', 'lastname'],
        'email' => ['payload', 'email']
    ],
    'insertId' => 'registration:id',
    'subQuery' => [
        'address' => [
            'query' => "INSERT INTO `address` SET __SET__",
            '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
                ['payload', 'address', $Constants::$REQUIRED]
            ],
            '__SET__' => [
                'registration_id' => ['insertIdParams', 'registration:id'],
                'address' => ['payload', 'address']
            ],
            'insertId' => 'address:id',
        ]
    ]
];
