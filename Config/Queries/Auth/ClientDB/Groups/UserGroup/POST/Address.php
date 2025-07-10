<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "INSERT INTO `address` SET __SET__", 
    '__SET__' => [
        ['column' => 'user_id', 'fetchFrom' => 'payload', 'fetchFromValue' => 'user_id', 'dataType' => DatabaseDataTypes::$INT], 
        ['column' => 'address', 'fetchFrom' => 'payload', 'fetchFromValue' => 'address'], 
    ], 
    '__INSERT-IDs__' => 'address:id', 
    '__TRIGGERS__' => [
        [
            '__ROUTE__' => [
                ['fetchFrom' => 'custom', 'fetchFromValue' => 'address'], 
                ['fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'address:id']
            ], 
            '__QUERY-STRING__' => [
                ['column' => 'param-1', 'fetchFrom' => 'custom', 'fetchFromValue' => 'address'], 
                ['column' => 'param-2', 'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'address:id']
            ], 
            '__METHOD__' => 'PATCH', 
            '__PAYLOAD__' => [
                ['column' => 'address', 'fetchFrom' => 'custom', 'fetchFromValue' => 'updated-address']
            ]
        ]
    ], 
    'isTransaction' => false
];
