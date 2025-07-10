<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `address` WHERE __WHERE__", 
    '__QUERY__' => "SELECT * FROM `address` WHERE __WHERE__", 
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No']
    ], 
    '__MODE__' => 'multipleRowFormat'
];
