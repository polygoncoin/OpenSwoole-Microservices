<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `address` WHERE __WHERE__",
    '__QUERY__' => "SELECT * FROM `address` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No']
    ],
    '__MODE__' => 'multipleRowFormat'
];
