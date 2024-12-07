<?php
namespace Microservices\Config\Queries\ClientDB\GET;

//return represents root for hierarchyData
return [
    'query' => "SELECT * FROM `category` WHERE `name` like CONCAT ('%', :name, '%');",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['payload', 'name', $Constants::$REQUIRED],
    ],
    '__WHERE__' => [
        'name' => ['payload', 'name']
    ],
    'mode' => 'multipleRowFormat',//Multiple rows returned.
];
