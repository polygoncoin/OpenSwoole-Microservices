<?php
namespace Microservices\Config\Routes\Auth\CommonRoutes\Client;

return [
    'category' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'UserGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-all.php',
        'search' => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'UserGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'SearchCategory.php',
        ],
        '{id:int|!0}' => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'UserGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-single.php',
        ]
    ],
    'registration' => [
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'UserGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-single.php',
        ],
    ],
    'address' => [
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'UserGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Address-single.php',
        ],
    ],
    'registration-with-address' => [
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'UserGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-With-Address-single.php',
        ],
    ],
    $Env::$routesRequestUri => [
        '__file__' => false,
        '{method:string|GET,POST,PUT,PATCH,DELETE}' => [
            '__file__' => false
        ]
    ]
];
