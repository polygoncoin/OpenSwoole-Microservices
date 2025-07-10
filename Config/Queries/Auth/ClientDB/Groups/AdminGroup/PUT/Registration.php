<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\PUT;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;

return array_merge(
    include Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Registration.php',
    [
        '__SET__' => [
            ['column' => 'firstname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'firstname'],
            ['column' => 'lastname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'lastname'],
            ['column' => 'email', 'fetchFrom' => 'payload', 'fetchFromValue' => 'email'],
            ['column' => 'username', 'fetchFrom' => 'payload', 'fetchFromValue' => 'username'],
            ['column' => 'password_hash', 'fetchFrom' => 'function', 'fetchFromValue' => function($sess) {
                return password_hash($sess['payload']['password'], PASSWORD_DEFAULT);
            }]
        ],
        '__WHERE__' => [
            ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
            ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id', 'dataType' => DatabaseDataTypes::$PrimaryKey]
        ],
    ]
);
