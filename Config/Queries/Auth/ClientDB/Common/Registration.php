<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Common;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "UPDATE `master_users` SET __SET__ WHERE __WHERE__",
    '__VALIDATE__' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', 'master_users'],
                'primary' => ['custom', 'user_id'],
                'id' => ['uriParams', 'id']
            ],
			'errorMessage' => 'Invalid registration id'
		],
	]
];
