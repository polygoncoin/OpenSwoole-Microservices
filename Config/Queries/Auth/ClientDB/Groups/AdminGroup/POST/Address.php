<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `address` SET __SET__",
    '__SET__' => [
        ['column' => 'user_id', 'fetchFrom' => 'payload', 'fetchFromValue' => 'user_id', 'dataType' => DatabaseDataTypes::$INT],
        ['column' => 'address', 'fetchFrom' => 'payload', 'fetchFromValue' => 'address'],
    ],
    '__INSERT-IDs__' => 'address:id'
];
