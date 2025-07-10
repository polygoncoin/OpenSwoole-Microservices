<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\POST;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    '__QUERY__' => "INSERT INTO `{$Env::$clients}` SET __SET__", 
    '__SET__' => [
        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'name'], 
        ['column' => 'comments', 'fetchFrom' => 'payload', 'fetchFromValue' => 'comments'], 
        ['column' => 'created_by', 'fetchFrom' => 'userDetails', 'fetchFromValue' => 'user_id'], 
        ['column' => 'created_on', 'fetchFrom' => 'custom', 'fetchFromValue' => date('Y-m-d H:i:s')], 
        ['column' => 'is_approved', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No']
    ], 
    '__INSERT-IDs__' => 'client_id', 
];
