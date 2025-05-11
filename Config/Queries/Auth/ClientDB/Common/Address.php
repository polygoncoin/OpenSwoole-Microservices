<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Common;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "UPDATE `address` SET __SET__ WHERE __WHERE__",
    '__VALIDATE__' => [
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
