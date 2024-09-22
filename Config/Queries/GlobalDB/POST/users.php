<?php
namespace Microservices\Config\Queries\GlobalDB\POST;

use Microservices\App\Constants;

return [
    'query' => "INSERT INTO `{$Env::$users}` SET __SET__",
    '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
        ['payload', 'name', Constants::$REQUIRED],
        ['payload', 'comments'],
    ],
    '__SET__' => [
        //column => [payload|readOnlySession|uriParams|insertIdParams|{custom}, key|{value}],
        'username' => ['payload', 'username'],
        'password_hash' => ['function', function() {
            return password_hash($this->c->httpRequest->input['payload']['password'], PASSWORD_DEFAULT);
         }],
        'group_id' => ['payload', 'group_id'],
        'comments' => ['payload', 'comments'],
        'created_by' => ['readOnlySession', 'user_id'],
        'created_on' => ['custom', date('Y-m-d H:i:s')],
        'is_approved' => ['custom', 'No'],
        'is_disabled' => ['custom', 'No'],
        'is_deleted' => ['custom', 'No']
    ],
    'insertId' => 'user_id',
];
