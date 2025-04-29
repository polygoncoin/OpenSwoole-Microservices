<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "SELECT * FROM `registration` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    'mode' => 'singleRowFormat'
];
