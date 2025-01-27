<?php
namespace Microservices\Config\Queries\GlobalDB\PATCH;

return [
    'query' => "UPDATE `{$Env::$groups}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['payload', 'name', $Constants::$REQUIRED],
        ['uriParams', 'group_id', $Constants::$REQUIRED],
    ],
    '__SET__' => [
        //column => [payload|userInfo|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
        'updated_by' => ['userInfo', 'user_id'],
        'updated_on' => ['custom', date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        'is_approved' => ['custom', 'Yes'],
        'is_disabled' => ['custom', 'No'],
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
	]
];
