<?php
namespace Microservices\Config\Queries\ClientDB\POST;

return [
    'query' => "INSERT INTO `address` SET __SET__",
    '__SET__' => [
        //column => [payload|userDetails|uriParams|insertIdParams|{custom}, key|{value}],
        'registration' => ['payload', 'registration_id'],
        'address' => ['payload', 'address'],
    ],
    'insertId' => 'address:id'
];
