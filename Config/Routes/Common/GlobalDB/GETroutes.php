<?php
namespace Microservices\Config\Routes\Common\Global;

return [
    'groups' => [
        '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/groups.php',
        '{group_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/groups.php',
        ],
    ],
    'clients' => [
        '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/clients.php',
        '{client_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/GET/clients.php',
        ],
    ]
];
