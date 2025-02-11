<?php
namespace Microservices\Config\Queries\GlobalDB\PATCH\enable;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "UPDATE `{$Env::$groups}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['uriParams', 'group_id', DatabaseDataTypes::$INT, Constants::$REQUIRED],
    ],
    '__SET__' => [
        //column => [payload|userDetails|uriParams|insertIdParams|{custom}, key|{value}],
        'is_disabled' => ['custom', 'No'],
        'updated_by' => ['userDetails', 'user_id'],
        'updated_on' => ['custom', date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        'is_disabled' => ['custom', 'Yes'],
        'is_deleted' => ['custom', 'No'],
        'group_id' => ['payload', 'group_id', DatabaseDataTypes::$INT]
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', Env::$groups],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT]
            ],
			'errorMessage' => 'Invalid Group Id'
		],
		[
			'fn' => 'checkColumnValueExist',
			'fnArgs' => [
                'table' => ['custom', Env::$groups],
                'column' => ['custom', 'is_deleted'],
                'columnValue' => ['custom', 'No'],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT],
            ],
			'errorMessage' => 'Record is deleted'
		],
		[
			'fn' => 'checkColumnValueExist',
			'fnArgs' => [
                'table' => ['custom', Env::$groups],
                'column' => ['custom', 'is_disabled'],
                'columnValue' => ['custom', 'Yes'],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT],
            ],
			'errorMessage' => 'Record is already enabled'
		]
	]
];
