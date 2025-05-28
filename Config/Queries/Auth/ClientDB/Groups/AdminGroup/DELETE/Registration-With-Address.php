<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "UPDATE `master_users` SET __SET__ WHERE __WHERE__",
    '__SET__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes']
    ],
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id', 'dataType' => DatabaseDataTypes::$PrimaryKey],
    ],
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "UPDATE `address` SET __SET__ WHERE __WHERE__",
            '__SET__' => [
                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes']
            ],
            '__WHERE__' => [
                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
                ['column' => 'user_id', 'fetchFrom' => 'payload', 'fetchFromValue' => 'user_id', 'dataType' => DatabaseDataTypes::$PrimaryKey],
                ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id', 'dataType' => DatabaseDataTypes::$PrimaryKey],
            ],
        ]
    ],
    '__VALIDATE__' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', 'master_users'],
                'primary' => ['custom', 'user_id'],
                'id' => ['uriParams', 'id']
            ],
			'errorMessage' => 'Invalid registration id'
		],
	],
    'useHierarchy' => true,
    'idempotentWindow' => 10
];
