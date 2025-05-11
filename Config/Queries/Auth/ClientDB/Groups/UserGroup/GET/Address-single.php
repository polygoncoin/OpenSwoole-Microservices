<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "SELECT * FROM `address` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    '__MODE__' => 'singleRowFormat'
];
