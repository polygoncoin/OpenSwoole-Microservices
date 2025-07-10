<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Common;

use Microservices\App\DatabaseDataTypes;

return [
    '__SQL-COMMENT__' => '',
    '__QUERY__' => "UPDATE `address` SET __SET__ WHERE __WHERE__",
    '__VALIDATE__' => [
        [
            'fn' => '_primaryKeyExist',
            'fnArgs' => [
                'table' => ['custom', 'address'],
                'primary' => ['custom', 'id'],
                'id' => ['uriParams', 'id']
            ],
            'errorMessage' => 'Invalid address id'
        ],
    ]
];
