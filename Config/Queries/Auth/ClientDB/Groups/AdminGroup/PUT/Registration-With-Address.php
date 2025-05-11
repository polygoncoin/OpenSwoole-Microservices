<?php
namespace Microservices\Config\Queries\Auth\ClientDB\POST;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "UPDATE `master_users` SET __SET__ WHERE __WHERE__",
    '__SET__' => [
        'firstname' => ['payload', 'firstname'],
        'lastname' => ['payload', 'lastname'],
        'email' => ['payload', 'email'],
        'username' => ['payload', 'username'],
        'password_hash' => ['function', function($session) {
            return password_hash($session['payload']['password'], PASSWORD_DEFAULT);
        }]
    ],
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'user_id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
    ],
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "UPDATE `address` SET __SET__ WHERE __WHERE__",
            '__SET__' => [
                'address' => ['payload', 'address']
            ],
            '__WHERE__' => [
                'is_deleted' => ['custom', 'No'],
                'id' => ['payload', 'id', DatabaseDataTypes::$PrimaryKey],
            ],
        ]
    ],
    '__VALIDATE__' => [
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
