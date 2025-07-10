<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\GET;

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'parent_id', 'fetchFrom' => 'custom', 'fetchFromValue' => 0],
        ['column' => 'is_deleted', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id']
    ],
    '__MODE__' => 'multipleRowFormat',
    '__SUB-QUERY__' => [
        'sub' => [
            '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
            '__WHERE__' => [
                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
                ['column' => 'parent_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:id'],
            ],
            '__MODE__' => 'multipleRowFormat',
            '__SUB-QUERY__' => [
                'subsub' => [
                    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
                    '__WHERE__' => [
                        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
                        ['column' => 'parent_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:sub:id'],
                    ],
                    '__MODE__' => 'multipleRowFormat',
                    '__SUB-QUERY__' => [
                        'subsubsub' => [
                            '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__",
                            '__WHERE__' => [
                                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
                                ['column' => 'parent_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:sub:subsub:id'], //data:address:id
                            ],
                            '__MODE__' => 'multipleRowFormat',
                        ]
                    ]
                ]
            ],
        ]
    ],
    'useResultSet' => true,
];
