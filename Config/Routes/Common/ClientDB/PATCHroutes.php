<?php
namespace Microservices\Config\Routes\Common\Client;

use Microservices\App\Constants;

return [
    'registration' => [
        '{id:int|!0}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/ClientDB/PATCH/Registration.php',
        ],
    ],
    'address' => [
        '{id:int|!0}'  => [
            '__file__' => Constants::$DOC_ROOT . '/Config/Queries/ClientDB/PATCH/Address.php',
        ],
    ]
];
