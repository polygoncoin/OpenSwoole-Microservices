<?php
namespace Microservices\Config\Queries\GlobalDB\PUT;

use Microservices\App\Constants;

return [
    'query' => "UPDATE `{$Env::$globalDB}`.`{$Env::$connections}` SET __SET__ WHERE __WHERE__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name'],
        ['payload', 'write_db_server_type'],
        ['payload', 'write_db_hostname'],
        ['payload', 'write_db_port'],
        ['payload', 'write_db_username'],
        ['payload', 'write_db_password'],
        ['payload', 'write_db_database'],
        ['payload', 'read_db_server_type'],
        ['payload', 'read_db_hostname'],
        ['payload', 'read_db_port'],
        ['payload', 'read_db_username'],
        ['payload', 'read_db_password'],
        ['payload', 'read_db_database'],
        ['payload', 'comments'],
        ['uriParams', 'connection_id', Constants::$REQUIRED]
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name', Constants::$REQUIRED],
        'write_db_server_type' => ['payload', 'write_db_server_type'],
        'write_db_hostname' => ['payload', 'write_db_hostname'],
        'write_db_port' => ['payload', 'write_db_port'],
        'write_db_username' => ['payload', 'write_db_username'],
        'write_db_password' => ['payload', 'write_db_password'],
        'write_db_database' => ['payload', 'write_db_database'],
        'read_db_server_type' => ['payload', 'read_db_server_type'],
        'read_db_hostname' => ['payload', 'read_db_hostname'],
        'read_db_port' => ['payload', 'read_db_port'],
        'read_db_username' => ['payload', 'read_db_username'],
        'read_db_password' => ['payload', 'read_db_password'],
        'read_db_database' => ['payload', 'read_db_database'],
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
