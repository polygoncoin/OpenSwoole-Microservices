<?php
namespace Microservices\Config\Queries\ClientDB\PUT;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'query' => "UPDATE `registration` SET firstname = :firstname WHERE id = :id",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'firstname', DatabaseDataTypes::$Default, Constants::$REQUIRED],
        ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey, Constants::$REQUIRED],
    ],
    '__SET__' => [
        'firstname' => ['payload', 'firstname'],
    ],
    '__WHERE__' => [
        'id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', 'registration'],
                'primary' => ['custom', 'id'],
                'id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
            ],
			'errorMessage' => 'Invalid registration id'
		],
	]
];
