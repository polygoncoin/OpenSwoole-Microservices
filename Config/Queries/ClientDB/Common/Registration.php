<?php
namespace Microservices\Config\Queries\ClientDB\Common;

use Microservices\App\Constants;

return [
    'query' => "UPDATE `{$this->clientDB}`.`registration` SET __SET__ WHERE __WHERE__",
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
