<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `address` SET __SET__",
    '__SET__' => [
        'user_id' => ['payload', 'user_id', DatabaseDataTypes::$INT],
        'address' => ['payload', 'address'],
    ],
    '__INSERT-ID__' => 'address:id'
];
