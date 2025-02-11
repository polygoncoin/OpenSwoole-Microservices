<?php
namespace Microservices\Config\Queries\ClientDB\Common;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "UPDATE `address` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey, Constants::$REQUIRED],
    ],
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', 'address'],
                'primary' => ['custom', 'id'],
                'id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
            ],
			'errorMessage' => 'Invalid address id'
		],
	]
];
