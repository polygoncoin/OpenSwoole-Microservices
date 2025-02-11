<?php
namespace Microservices\Config\Queries\ClientDB\GET;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "SELECT * FROM `category` WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey, Constants::$REQUIRED],
    ],
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    'mode' => 'singleRowFormat'//Single row returned.
];
