<?php
namespace Microservices\Config\Queries\ClientDB\GET;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "SELECT * FROM `address` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    'mode' => 'singleRowFormat'
];
