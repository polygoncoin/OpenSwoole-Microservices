<?php
namespace Microservices\Config\Queries\GlobalDB\POST;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "INSERT INTO `{$Env::$groups}` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', DatabaseDataTypes::$Default, Constants::$REQUIRED],
        ['payload', 'client_id', DatabaseDataTypes::$INT],
        ['payload', 'connection_id', DatabaseDataTypes::$INT],
        ['payload', 'allowed_ips'],
        ['payload', 'comments'],
    ],
    '__SET__' => [
        //column => [payload|userDetails|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
        'client_id' => ['payload', 'client_id', DatabaseDataTypes::$INT],
        'connection_id' => ['payload', 'connection_id', DatabaseDataTypes::$INT],
        'allowed_ips' => ['payload', 'allowed_ips'],
        'comments' => ['payload', 'comments'],
        'created_by' => ['userDetails', 'user_id'],
        'created_on' => ['custom', date('Y-m-d H:i:s')],
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No']
    ],
    'insertId' => 'group_id',
];
