<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "SELECT * FROM `master_users` WHERE __WHERE__",
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'user_id' => ['uriParams','id']
    ],
    '__MODE__' => 'multipleRowFormat',
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "SELECT * FROM `address` WHERE __WHERE__",
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'user_id' => ['sqlResults', 'return:user_id'],
            ],
            '__MODE__' => 'multipleRowFormat',
        ]
    ],
    'useResultSet' => true
];
