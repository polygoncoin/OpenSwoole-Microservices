<?php
namespace Microservices\Config\Routes\Auth\GroupRoutes\AdminGroup;

return [
    'registration' => [
        '__FILE__' => $Constants::$DOC_ROOT . 
            DIRECTORY_SEPARATOR . 'Config' . 
            DIRECTORY_SEPARATOR . 'Queries' . 
            DIRECTORY_SEPARATOR . 'Open' . 
            DIRECTORY_SEPARATOR . 'POST' . 
            DIRECTORY_SEPARATOR . 'Registration.php',
    ],
    'registration-with-address' => [
        '__FILE__' => $Constants::$DOC_ROOT . 
            DIRECTORY_SEPARATOR . 'Config' . 
            DIRECTORY_SEPARATOR . 'Queries' . 
            DIRECTORY_SEPARATOR . 'Open' . 
            DIRECTORY_SEPARATOR . 'POST' . 
            DIRECTORY_SEPARATOR . 'Registration-With-Address.php',
    ],
];
