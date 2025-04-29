<?php
namespace Microservices\Config\Queries\Open\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "SELECT * FROM `category` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    'mode' => 'singleRowFormat'
];
