<?php
namespace Microservices\Config\Queries\ClientDB\PUT;

use Microservices\App\Constants;

return [
    'query' => "UPDATE `{$Env::$clientDB}`.`registration` SET firstname = :firstname WHERE id = :id",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'firstname', Constants::$REQUIRED],
        ['uriParams', 'id', Constants::$REQUIRED],
    ],
    '__SET__' => [
        'firstname' => ['payload', 'firstname'],
    ],
    '__WHERE__' => [
        'id' => ['uriParams', 'id']
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', 'registration'],
                'primary' => ['custom', 'id'],
                'id' => ['uriParams', 'id']
            ],
			'errorMessage' => 'Invalid registration id'
		],
	]
];
