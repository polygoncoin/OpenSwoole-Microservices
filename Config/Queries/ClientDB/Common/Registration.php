<?php
namespace Microservices\Config\Queries\ClientDB\Common;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

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
