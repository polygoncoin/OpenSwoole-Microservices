<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\GET;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "SELECT * FROM `master_users` WHERE __WHERE__", 
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id']
    ], 
    '__MODE__' => 'multipleRowFormat', 
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "SELECT * FROM `address` WHERE __WHERE__", 
            '__WHERE__' => [
                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
                ['column' => 'user_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:user_id'], 
            ], 
            '__MODE__' => 'multipleRowFormat', 
        ]
    ], 
    'useResultSet' => true
];
