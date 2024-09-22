<?php
namespace Microservices\Config\Queries\GlobalDB\POST;

use Microservices\App\Constants;

return [
    'query' => "INSERT INTO `{$Env::$connections}` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', Constants::$REQUIRED],
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
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
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
        'created_by' => ['readOnlySession', 'user_id'],
        'created_on' => ['custom', date('Y-m-d H:i:s')],
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No']
    ],
    'insertId' => 'connection_id',
];
