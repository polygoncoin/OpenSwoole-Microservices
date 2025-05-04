<?php
namespace Microservices\Config\Routes\Auth\ClientDB\Groups\AdminGroup;

return [
    'category' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-all.php',
        'search' => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-search.php',
        ],
        '{id:int|!0}' => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-Single.php',
        ]
    ],
    'registration' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-all.php',
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-single.php',
        ],
    ],
    'address' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Address-all.php',
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Address-single.php',
        ],
    ],
    'registration-with-address' => [
        '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-With-Address-all.php',
        '{id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Groups' . DIRECTORY_SEPARATOR . 'AdminGroup' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Registration-With-Address-single.php',
        ],
    ],
    $Env::$routesRequestUri => [
        '__file__' => false,
        '{method:string|GET,POST,PUT,PATCH,DELETE}' => [
            '__file__' => false
        ]
    ]
];

