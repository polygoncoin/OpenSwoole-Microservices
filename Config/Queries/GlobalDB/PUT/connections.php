<?php
namespace Microservices\Config\Queries\GlobalDB\PUT;

use Microservices\App\Constants;

return [
    'query' => "UPDATE `{$Env::$globalDB}`.`{$Env::$connections}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name'],
        ['payload', 'db_server_type'],
        ['payload', 'db_hostname'],
        ['payload', 'db_username'],
        ['payload', 'db_password'],
        ['payload', 'db_database'],
        ['payload', 'comments'],
        ['uriParams', 'connection_id', Constants::$REQUIRED]
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name', Constants::$REQUIRED],
        'db_server_type' => ['payload', 'db_server_type'],
        'db_hostname' => ['payload', 'db_hostname'],
        'db_username' => ['payload', 'db_username'],
        'db_password' => ['payload', 'db_password'],
        'db_database' => ['payload', 'db_database'],
        'comments' => ['payload', 'comments'],
        'updated_by' => ['readOnlySession', 'user_id'],
        'updated_on' => ['custom', date('Y-m-d H:i:s')]
    ],
    '__WHERE__' => [
        'is_approved' => ['custom', 'Yes'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No'],
        'connection_id' => ['uriParams', 'connection_id']
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
	]
];
