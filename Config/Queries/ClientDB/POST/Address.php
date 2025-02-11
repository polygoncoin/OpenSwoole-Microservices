<?php
namespace Microservices\Config\Queries\ClientDB\POST;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "INSERT INTO `address` SET __SET__",
    '__SET__' => [
        //column => [payload|userDetails|uriParams|insertIdParams|{custom}, key|{value}],
        'registration' => ['payload', 'registration_id', DatabaseDataTypes::$INT],
        'address' => ['payload', 'address'],
    ],
    'insertId' => 'address:id'
];
