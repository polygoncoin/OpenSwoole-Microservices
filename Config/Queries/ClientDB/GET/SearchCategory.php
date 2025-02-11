<?php
namespace Microservices\Config\Queries\ClientDB\GET;

//return represents root for hierarchyData
use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "SELECT * FROM `category` WHERE `name` like CONCAT ('%', :name, '%');",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', DatabaseDataTypes::$Default, Constants::$REQUIRED],
    ],
    '__WHERE__' => [
        'name' => ['payload', 'name']
    ],
    'mode' => 'multipleRowFormat',//Multiple rows returned.
];
