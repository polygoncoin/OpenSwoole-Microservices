<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\GET;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "SELECT * FROM `category` WHERE `name` like CONCAT ('%', :name, '%');",
    '__WHERE__' => [
        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'name']
    ],
    '__MODE__' => 'multipleRowFormat',
];
