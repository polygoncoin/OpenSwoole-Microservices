<?php
namespace Microservices\Config\Queries\Open\GET;


return [
    '__QUERY__' => "SELECT * FROM `category` WHERE `name` like CONCAT ('%', :name, '%');",
    '__WHERE__' => [
        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'name']
    ],
    '__MODE__' => 'multipleRowFormat',
];
