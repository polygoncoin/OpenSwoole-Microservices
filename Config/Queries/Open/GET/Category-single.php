<?php
namespace Microservices\Config\Queries\Open\GET;


return [
    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'is_deleted', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id'],
    ],
    '__MODE__' => 'singleRowFormat'
];
