<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\PATCH;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    '__QUERY__' => "UPDATE `{$Env::$groups}` SET __SET__ WHERE __WHERE__",
    '__SET__' => [
        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'name'],
        ['column' => 'updated_by', 'fetchFrom' => 'userDetails', 'fetchFromValue' => 'user_id'],
        ['column' => 'updated_on', 'fetchFrom' => 'custom', 'fetchFromValue' => date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        ['column' => 'is_approved', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes'],
        ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'group_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'group_id', 'dataType' => DatabaseDataTypes::$INT]
    ],
    '__VALIDATE__' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', Env::$groups],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT]
            ],
			'errorMessage' => 'Invalid Group Id'
		],
	]
];
