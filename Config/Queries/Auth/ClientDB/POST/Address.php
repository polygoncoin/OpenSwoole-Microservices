<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "INSERT INTO `address` SET __SET__",
    '__SET__' => [
        'registration' => ['payload', 'registration_id', DatabaseDataTypes::$INT],
        'address' => ['payload', 'address'],
    ],
    'insertId' => 'address:id'
];
