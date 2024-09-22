<?php
namespace Microservices\Config\Queries\ClientDB\GET;

use Microservices\App\Constants;

return [
    'query' => "SELECT * FROM `address` WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['uriParams', 'id', Constants::$REQUIRED],
    ],
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    'mode' => 'singleRowFormat'//Single row returned.
];
