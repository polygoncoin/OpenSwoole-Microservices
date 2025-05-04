<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "UPDATE `master_users` SET __SET__ WHERE __WHERE__",
    '__SET__' => [
        'firstname' => ['payload', 'firstname'],
        'lastname' => ['payload', 'lastname'],
        'email' => ['payload', 'email'],
    ],
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'user_id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
    ],
    'subQuery' => [
        'address' => [
            'query' => "UPDATE `address` SET __SET__ WHERE __WHERE__",
            '__SET__' => [
                'address' => ['payload', 'address']
            ],
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'id' => ['payload', 'id', DatabaseDataTypes::$PrimaryKey],
            ],
        ]
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', 'master_users'],
                'primary' => ['custom', 'user_id'],
                'id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
            ],
			'errorMessage' => 'Invalid registration id'
		],
	],
    'useHierarchy' => true,
    'idempotentWindow' => 10
];
