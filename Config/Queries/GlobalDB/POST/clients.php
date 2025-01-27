<?php
namespace Microservices\Config\Queries\GlobalDB\POST;

return [
    'query' => "INSERT INTO `{$Env::$clients}` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
        ['payload', 'name', $Constants::$REQUIRED],
        ['payload', 'comments']
    ],
    '__SET__' => [
        //column => [payload|userInfo|uriParams|insertIdParams|{custom}, key|{value}],
        'name' => ['payload', 'name'],
        'comments' => ['payload', 'comments'],
        'created_by' => ['userInfo', 'user_id'],
        'created_on' => ['custom', date('Y-m-d H:i:s')],
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No']
    ],
    'insertId' => 'client_id',
];
