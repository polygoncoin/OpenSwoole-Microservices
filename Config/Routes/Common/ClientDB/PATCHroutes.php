<?php
namespace Microservices\Config\Routes\Common\Client;

return [
    'registration' => [
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'Registration.php',
        ],
    ],
    'address' => [
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'Address.php',
        ],
    ]
];
