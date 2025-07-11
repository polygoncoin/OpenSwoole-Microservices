<?php
namespace Microservices\Config\Routes\Auth\CommonRoutes\Client;

return [
    'registration' => [
        '{id:int|!0}'  => [
            '__FILE__' => $Constants::$DOC_ROOT . 
                DIRECTORY_SEPARATOR . 'Config' . 
                DIRECTORY_SEPARATOR . 'Queries' . 
                DIRECTORY_SEPARATOR . 'Auth' . 
                DIRECTORY_SEPARATOR . 'ClientDB' . 
                DIRECTORY_SEPARATOR . 'Groups' . 
                DIRECTORY_SEPARATOR . 'UserGroup' . 
                DIRECTORY_SEPARATOR . 'PATCH' . 
                DIRECTORY_SEPARATOR . 'Registration.php',
        ],
    ],
    'address' => [
        '{id:int|!0}'  => [
            '__FILE__' => $Constants::$DOC_ROOT . 
                DIRECTORY_SEPARATOR . 'Config' . 
                DIRECTORY_SEPARATOR . 'Queries' . 
                DIRECTORY_SEPARATOR . 'Auth' . 
                DIRECTORY_SEPARATOR . 'ClientDB' . 
                DIRECTORY_SEPARATOR . 'Groups' . 
                DIRECTORY_SEPARATOR . 'UserGroup' . 
                DIRECTORY_SEPARATOR . 'PATCH' . 
                DIRECTORY_SEPARATOR . 'Address.php',
        ],
    ]
];
