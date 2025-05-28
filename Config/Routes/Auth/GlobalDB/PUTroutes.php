<?php
namespace Microservices\Config\Routes\Auth\CommonRoutes\GlobalDB;

return [
    'group' => [
        '{group_id:int|!0}'  => [
            '__FILE__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PUT' . DIRECTORY_SEPARATOR . 'groups.php',
        ],
    ],
    'client' => [
        '{client_id:int|!0}'  => [
            '__FILE__' => $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'GlobalDB' . DIRECTORY_SEPARATOR . 'PUT' . DIRECTORY_SEPARATOR . 'clients.php',
        ],
    ],
];
