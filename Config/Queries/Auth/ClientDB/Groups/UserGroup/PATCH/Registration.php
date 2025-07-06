<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\PATCH;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;

return array_merge(
    include Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Registration.php',
    [
        '__SET__' => [
            ['column' => 'firstname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'firstname'],
            ['column' => 'lastname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'lastname'],
            ['column' => 'email', 'fetchFrom' => 'payload', 'fetchFromValue' => 'email'],
        ],
        '__WHERE__' => [
            ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id', 'dataType' => DatabaseDataTypes::$PrimaryKey]
        ],
    ]
);
