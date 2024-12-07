<?php
namespace Microservices\Config\Queries\GlobalDB\PUT;

return [
    'query' => "UPDATE `{$Env::$clients}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['payload', 'name'],
        ['payload', 'comments'],
        ['uriParams', 'client_id', $Constants::$REQUIRED]
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
        'comments' => ['payload', 'comments'],
        'updated_by' => ['readOnlySession', 'user_id'],
        'updated_on' => ['custom', date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        'is_approved' => ['custom', 'Yes'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No'],
        'client_id' => ['uriParams', 'client_id']
    ],
    'validate' => [
		[
			'fn' => 'primaryKeyExist',
			'fnArgs' => [
                'table' => ['custom', $Env::$clients],
                'primary' => ['custom', 'client_id'],
                'id' => ['payload', 'client_id']
            ],
			'errorMessage' => 'Invalid Client Id'
		],
	]
];
