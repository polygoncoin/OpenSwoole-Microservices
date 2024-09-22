<?php
namespace Microservices\Config\Queries\GlobalDB\PUT;

use Microservices\App\Constants;

return [
    'query' => "UPDATE `{$Env::$connections}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name'],
        ['payload', 'master_db_server_type'],
        ['payload', 'master_db_hostname'],
        ['payload', 'master_db_port'],
        ['payload', 'master_db_username'],
        ['payload', 'master_db_password'],
        ['payload', 'master_db_database'],
        ['payload', 'slave_db_server_type'],
        ['payload', 'slave_db_hostname'],
        ['payload', 'slave_db_port'],
        ['payload', 'slave_db_username'],
        ['payload', 'slave_db_password'],
        ['payload', 'slave_db_database'],
        ['payload', 'comments'],
        ['uriParams', 'connection_id', Constants::$REQUIRED]
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name', Constants::$REQUIRED],
        'master_db_server_type' => ['payload', 'master_db_server_type'],
        'master_db_hostname' => ['payload', 'master_db_hostname'],
        'master_db_port' => ['payload', 'master_db_port'],
        'master_db_username' => ['payload', 'master_db_username'],
        'master_db_password' => ['payload', 'master_db_password'],
        'master_db_database' => ['payload', 'master_db_database'],
        'slave_db_server_type' => ['payload', 'slave_db_server_type'],
        'slave_db_hostname' => ['payload', 'slave_db_hostname'],
        'slave_db_port' => ['payload', 'slave_db_port'],
        'slave_db_username' => ['payload', 'slave_db_username'],
        'slave_db_password' => ['payload', 'slave_db_password'],
        'slave_db_database' => ['payload', 'slave_db_database'],
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
