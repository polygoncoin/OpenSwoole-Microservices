<?php
namespace Microservices\Config\Queries\Open\GET;


return [
    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    '__MODE__' => 'singleRowFormat'
];
