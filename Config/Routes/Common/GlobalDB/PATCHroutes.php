<?php
namespace Microservices\Config\Routes\Common\Global;

return [
    'group' => [
        '{group_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/groups.php',
            'approve'  => [
                '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/approve/groups.php',
            ],
            'disable'  => [
                '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/disable/groups.php',
            ],
            'enable'  => [
                '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/enable/groups.php',
            ],
        ],
    ],
    'client' => [
        '{client_id:int|!0}'  => [
            '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/clients.php',
            'approve'  => [
                '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/approve/clients.php',
            ],
            'disable'  => [
                '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/disable/clients.php',
            ],
            'enable'  => [
                '__file__' => $Constants::$DOC_ROOT . '/Config/Queries/GlobalDB/PATCH/enable/clients.php',
            ],
        ],
    ],
];
