<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\POST;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    '__QUERY__' => "INSERT INTO `{$Env::$groups}` SET __SET__",
    '__SET__' => [
        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'name'],
        ['column' => 'client_id', 'fetchFrom' => 'payload', 'fetchFromValue' => 'client_id', 'dataType' => DatabaseDataTypes::$INT],
        ['column' => 'connection_id', 'fetchFrom' => 'payload', 'fetchFromValue' => 'connection_id', 'dataType' => DatabaseDataTypes::$INT],
        ['column' => 'allowed_ips', 'fetchFrom' => 'payload', 'fetchFromValue' => 'allowed_ips'],
        ['column' => 'comments', 'fetchFrom' => 'payload', 'fetchFromValue' => 'comments'],
        ['column' => 'created_by', 'fetchFrom' => 'userDetails', 'fetchFromValue' => 'user_id'],
        ['column' => 'created_on', 'fetchFrom' => 'custom', 'fetchFromValue' => date('Y-m-d H:i:s')],
        ['column' => 'is_approved', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No']
    ],
    '__INSERT-IDs__' => 'group_id',
];
