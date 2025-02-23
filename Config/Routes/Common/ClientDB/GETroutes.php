<?php
namespace Microservices\Config\Routes\Common\Client;

return [
    'category' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category.php',
        'search' => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'SearchCategory.php',
        ],
        '{id:int|!0}' => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-Single.php',
        ]
    ],
    'registration' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-all.php',
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-single.php',
        ],
    ],
    'address' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Address-all.php',
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Address-single.php',
        ],
    ],
    $Env::$routesRequestUri => [
        '__file__' => false,
        '{method:string|GET,POST,PUT,PATCH,DELETE}' => [
            '__file__' => false
        ]
    ]
];
