<?php
namespace Microservices\Config\Queries\ClientDB\GET;

return [
    'query' => "SELECT * FROM `registration` WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['uriParams', 'id', $Constants::$REQUIRED],
    ],
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams','id']
    ],
    'mode' => 'singleRowFormat'//Single row returned.
];
