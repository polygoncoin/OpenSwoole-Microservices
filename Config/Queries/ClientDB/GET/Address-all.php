<?php
namespace Microservices\Config\Queries\ClientDB\GET;

use Microservices\App\Constants;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `address` WHERE __WHERE__",
    'query' => "SELECT * FROM `address` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No']
    ],
    'mode' => 'multipleRowFormat'//Multiple rows returned.
];
