<?php
namespace Microservices\Config\Queries\ClientDB\PUT;

return array_merge(
    include Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Registration.php',
    [
        '__CONFIG__' => [
            ['payload', 'firstname', DatabaseDataTypes::$Default, Constants::$REQUIRED],
            ['payload', 'lastname', DatabaseDataTypes::$Default, Constants::$REQUIRED],
            ['payload', 'email', DatabaseDataTypes::$Default, Constants::$REQUIRED],
            ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey, Constants::$REQUIRED],
        ],
        '__SET__' => [
            'firstname' => ['payload', 'firstname'],
            'lastname' => ['payload', 'lastname'],
            'email' => ['payload', 'email']
        ],
        '__WHERE__' => [
            'is_deleted' => ['custom', 'No'],
            'id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
        ],
    ]
);
