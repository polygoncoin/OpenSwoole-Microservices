<?php
namespace Microservices\Config\Queries\Auth\ClientDB\PUT;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;

return array_merge(
    include Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Registration.php',
    [
        '__SET__' => [
            'firstname' => ['payload', 'firstname'],
            'lastname' => ['payload', 'lastname'],
            'email' => ['payload', 'email'],
            'username' => ['payload', 'username'],
            'password_hash' => ['function', function($session) {
                return password_hash($session['payload']['password'], PASSWORD_DEFAULT);
            }]
        ],
        '__WHERE__' => [
            'is_deleted' => ['custom', 'No'],
            'user_id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
        ],
    ]
);
