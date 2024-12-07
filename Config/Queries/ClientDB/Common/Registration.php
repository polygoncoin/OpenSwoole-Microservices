<?php
namespace Microservices\Config\Queries\ClientDB\Common;

return [
    'query' => "UPDATE `registration` SET __SET__ WHERE __WHERE__",
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
