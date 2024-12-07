<?php
namespace Microservices\Config\Routes\Common\Global;

return [
    'group' => [
        '{group_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/DELETE/groups.php',
        ],
    ],
    'client' => [
        '{client_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/DELETE/clients.php',
        ],
    ],
];
