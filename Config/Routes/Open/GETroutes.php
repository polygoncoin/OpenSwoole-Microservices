<?php
namespace Microservices\Config\Routes\Open;

return [
    'category' => [
        '__FILE__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Open' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-all.php',
        'search' => [
            '__FILE__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Open' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-search.php',
        ],
        '{id:int|!0}' => [
            '__FILE__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Open' . DIRECTORY_SEPARATOR . 'GET' . DIRECTORY_SEPARATOR . 'Category-Single.php',
        ]
    ]
];
