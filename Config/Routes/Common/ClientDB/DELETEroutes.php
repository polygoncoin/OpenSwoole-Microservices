<?php
namespace Microservices\Config\Routes\Common\Client;

use Microservices\App\Constants;

return [
    'registration' => [
        '{id:int|!0}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/ClientDB/DELETE/Registration.php',
        ],
    ],
    'address' => [
        '{id:int|!0}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/ClientDB/DELETE/Address.php',
        ],
    ]
];
