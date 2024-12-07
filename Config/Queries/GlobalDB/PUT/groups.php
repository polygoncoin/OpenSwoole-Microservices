<?php
namespace Microservices\Config\Queries\GlobalDB\PUT;

return [
    'query' => "UPDATE `{$Env::$groups}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['payload', 'name'],
        ['payload', 'client_id'],
        ['payload', 'connection_id'],
        ['payload', 'allowed_ips'],
        ['payload', 'comments'],
        ['uriParams', 'group_id', $Constants::$REQUIRED]
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
        'client_id' => ['payload', 'client_id'],
        'connection_id' => ['payload', 'connection_id'],
        'allowed_ips' => ['payload', 'allowed_ips'],
        'comments' => ['payload', 'comments'],
        'updated_by' => ['readOnlySession', 'user_id'],
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
