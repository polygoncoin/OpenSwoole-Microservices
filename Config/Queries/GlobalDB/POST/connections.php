<?php
namespace Microservices\Config\Queries\GlobalDB\POST;

use Microservices\App\Constants;

return [
    'query' => "INSERT INTO `{$Env::$globalDB}`.`{$Env::$connections}` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', Constants::$REQUIRED],
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
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
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
        'created_by' => ['readOnlySession', 'user_id'],
        'created_on' => ['custom', date('Y-m-d H:i:s')],
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No']
    ],
    'insertId' => 'connection_id',
];
