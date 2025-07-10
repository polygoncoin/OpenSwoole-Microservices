<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\GET;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "SELECT * FROM `address` WHERE __WHERE__", 
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'is_deleted', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id']
    ], 
    '__MODE__' => 'singleRowFormat'
];
