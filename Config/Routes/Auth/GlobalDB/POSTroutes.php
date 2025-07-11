<?php
namespace Microservices\Config\Routes\Auth\CommonRoutes\GlobalDB;

return [
    'group' => [
        '__FILE__' => $Constants::$DOC_ROOT . 
            DIRECTORY_SEPARATOR . 'Config' . 
            DIRECTORY_SEPARATOR . 'Queries' . 
            DIRECTORY_SEPARATOR . 'Auth' . 
            DIRECTORY_SEPARATOR . 'GlobalDB' . 
            DIRECTORY_SEPARATOR . 'POST' . 
            DIRECTORY_SEPARATOR . 'groups.php',
    ],
    'client' => [
        '__FILE__' => $Constants::$DOC_ROOT . 
            DIRECTORY_SEPARATOR . 'Config' . 
            DIRECTORY_SEPARATOR . 'Queries' . 
            DIRECTORY_SEPARATOR . 'Auth' . 
            DIRECTORY_SEPARATOR . 'GlobalDB' . 
            DIRECTORY_SEPARATOR . 'POST' . 
            DIRECTORY_SEPARATOR . 'clients.php',
    ],
];
