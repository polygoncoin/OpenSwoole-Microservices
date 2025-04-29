<?php
namespace Microservices\Config\Routes\Auth\Common\GlobalDB;

return [
    'group' => [
        '{group_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'groups.php',
            'approve'  => [
                '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'approve' . DIRECTORY_SEPARATOR . 'groups.php',
            ],
            'disable'  => [
                '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'disable' . DIRECTORY_SEPARATOR . 'groups.php',
            ],
            'enable'  => [
                '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'enable' . DIRECTORY_SEPARATOR . 'groups.php',
            ],
        ],
    ],
    'client' => [
        '{client_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'clients.php',
            'approve'  => [
                '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'approve' . DIRECTORY_SEPARATOR . 'clients.php',
            ],
            'disable'  => [
                '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'disable' . DIRECTORY_SEPARATOR . 'clients.php',
            ],
            'enable'  => [
                '__file__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PATCH' . DIRECTORY_SEPARATOR . 'enable' . DIRECTORY_SEPARATOR . 'clients.php',
            ],
        ],
    ],
];
