<?php
namespace Microservices\Config\Queries\GlobalDB\POST;

use Microservices\App\Constants;

return [
    'query' => "INSERT INTO `{$Env::$globalDB}`.`{$Env::$connections}` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', Constants::$REQUIRED],
        ['payload', 'db_server_type'],
        ['payload', 'db_hostname'],
        ['payload', 'db_username'],
        ['payload', 'db_password'],
        ['payload', 'db_database'],
        ['payload', 'comments'],
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
        'db_server_type' => ['payload', 'db_server_type'],
        'db_hostname' => ['payload', 'db_hostname'],
        'db_username' => ['payload', 'db_username'],
        'db_password' => ['payload', 'db_password'],
        'db_database' => ['payload', 'db_database'],
        'comments' => ['payload', 'comments'],
        'created_by' => ['readOnlySession', 'user_id'],
        'created_on' => ['custom', date('Y-m-d H:i:s')],
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No']
    ],
    'insertId' => 'connection_id',
];
