<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Common;

use Microservices\App\DatabaseDataTypes;

return [
    'query' => "UPDATE `registration` SET __SET__ WHERE __WHERE__",
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
