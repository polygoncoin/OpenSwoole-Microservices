<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\POST;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "INSERT INTO `{$Env::$clients}` SET __SET__",
    '__SET__' => [
        'name' => ['payload', 'name'],
        'comments' => ['payload', 'comments'],
        'created_by' => ['userDetails', 'user_id'],
        'created_on' => ['custom', date('Y-m-d H:i:s')],
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No']
    ],
    'insertId' => 'client_id',
];
