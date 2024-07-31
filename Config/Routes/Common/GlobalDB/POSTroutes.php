<?php
namespace Microservices\Config\Routes\Common\Global;

use Microservices\App\Constants;

return [
    'groups' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/POST/groups.php',
    ],
    'users' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/POST/users.php',
    ],
    'connections' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/POST/connections.php',
    ],
    'clients' => [
        '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/POST/clients.php',
    ],
];
