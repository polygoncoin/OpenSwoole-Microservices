<?php
namespace Microservices\Config\Queries\GlobalDB\DELETE;

return [
    'query' => "UPDATE `{$Env::$groups}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['uriParams', 'group_id', $Constants::$REQUIRED],
    ],
    '__SET__' => [
        //column => [payload|userDetails|uriParams|insertIdParams|{custom}, key|{value}],
        'is_deleted' => ['custom', 'Yes'],
        'updated_by' => ['userDetails', 'user_id'],
        'updated_on' => ['custom', date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        'is_deleted' => ['custom', 'No'],
        'group_id' => ['uriParams', 'group_id']
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', $Env::$groups],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id']
            ],
			'errorMessage' => 'Invalid Group Id'
		],
		[
			'fn' => 'checkColumnValueExist',
			'fnArgs' => [
                'table' => ['custom', $Env::$groups],
                'column' => ['custom', 'is_deleted'],
                'columnValue' => ['custom', 'No'],
                'primary' => ['custom', 'group_id'],
                'id' => ['payload', 'group_id'],
            ],
			'errorMessage' => 'Record is already deleted'
		]
	]
];
