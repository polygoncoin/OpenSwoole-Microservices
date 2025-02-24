<?php
namespace Microservices\Config\Queries\ClientDB\GET;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "SELECT * FROM `category` WHERE `name` like CONCAT ('%', :name, '%');",
    '__WHERE__' => [
        'name' => ['payload', 'name']
    ],
    'mode' => 'multipleRowFormat',
];
