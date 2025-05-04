<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "SELECT * FROM `category` WHERE `name` like CONCAT ('%', :name, '%');",
    '__WHERE__' => [
        'name' => ['payload', 'name']
    ],
    'mode' => 'multipleRowFormat',
];
