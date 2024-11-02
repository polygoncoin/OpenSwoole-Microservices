<?php
namespace Microservices\Config\Routes\Common\Global;

use Microservices\App\Constants;

return [
    'groups' => [
        '{group_id:int|!0}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/DELETE/groups.php',
        ],
    ],
    'clients' => [
        '{client_id:int|!0}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/DELETE/clients.php',
        ],
    ],
];
