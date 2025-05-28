<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\PATCH;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    '__QUERY__' => "UPDATE `{$Env::$clients}` SET __SET__ WHERE __WHERE__",
    '__SET__' => [
        ['column' => 'name', 'fetchFrom' => 'payload', 'fetchFromValue' => 'name'],
        ['column' => 'updated_by', 'fetchFrom' => 'userDetails', 'fetchFromValue' => 'user_id'],
        ['column' => 'updated_on', 'fetchFrom' => 'custom', 'fetchFromValue' => date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        ['column' => 'is_approved', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes'],
        ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'client_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'client_id', 'dataType' => DatabaseDataTypes::$INT]
    ],
    '__VALIDATE__' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', Env::$clients],
                'primary' => ['custom', 'client_id'],
                'id' => ['payload', 'client_id', DatabaseDataTypes::$INT]
            ],
			'errorMessage' => 'Invalid Client Id'
		],
	]
];
