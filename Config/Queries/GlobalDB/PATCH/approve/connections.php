<?php
namespace Microservices\Config\Queries\GlobalDB\PATCH\approve;

use Microservices\App\Constants;

return [
    'query' => "UPDATE `{$Env::$connections}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['uriParams', 'connection_id', Constants::$REQUIRED],
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'is_approved' => ['custom', 'Yes'],
        'updated_by' => ['readOnlySession', 'user_id'],
        'updated_on' => ['custom', date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No'],
        'connection_id' => ['payload', 'connection_id']
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', Env::$connections],
                'primary' => ['custom', 'connection_id'],
                'id' => ['payload', 'connection_id']
            ],
			'errorMessage' => 'Invalid Connection Id'
		],
		[
			'fn' => 'checkColumnValueExist',
			'fnArgs' => [
                'table' => ['custom', Env::$connections],
                'column' => ['custom', 'is_deleted'],
                'columnValue' => ['custom', 'No'],
                'primary' => ['custom', 'connection_id'],
                'id' => ['payload', 'connection_id'],
            ],
			'errorMessage' => 'Record is deleted'
		],
		[
			'fn' => 'checkColumnValueExist',
			'fnArgs' => [
                'table' => ['custom', Env::$connections],
                'column' => ['custom', 'is_approved'],
                'columnValue' => ['custom', 'No'],
                'primary' => ['custom', 'connection_id'],
                'id' => ['payload', 'connection_id'],
            ],
			'errorMessage' => 'Record is already approved'
		]
	]
];
