<?php
namespace Microservices\Config\Routes\Common\Global;

use Microservices\App\Constants;

return [
    'groups' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/groups.php',
        '{group_id:int}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/groups.php',
        ],
    ],
    'users' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/users.php',
        '{user_id:int}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/users.php',
        ],
    ],
    'connections' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/connections.php',
        '{connection_id:int}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/connections.php',
        ],
    ],
    'clients' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/clients.php',
        '{client_id:int}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/clients.php',
        ],
    ]
];
