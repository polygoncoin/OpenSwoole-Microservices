<?php
namespace Microservices\Config\Queries\GlobalDB\PATCH;

use Microservices\App\Constants;

return [
    'query' => "UPDATE `{$this->globalDB}`.`{$Env::$users}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', Constants::$REQUIRED],
        ['uriParams', 'user_id', Constants::$REQUIRED],
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'password_hash' => ['payload', 'password_hash'],
        'updated_by' => ['readOnlySession', 'user_id'],
        'updated_on' => ['custom', date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        'is_approved' => ['custom', 'Yes'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No'],
        'user_id' => ['uriParams', 'user_id']
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', Env::$users],
                'primary' => ['custom', 'user_id'],
                'id' => ['payload', 'user_id']
            ],
			'errorMessage' => 'Invalid User Id'
		],
	]
];
