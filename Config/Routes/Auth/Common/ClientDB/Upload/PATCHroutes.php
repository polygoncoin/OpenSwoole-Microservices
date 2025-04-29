<?php
namespace Microservices\Config\Routes\Auth\Common\ClientDB\Client;

return [
    $Env::$uploadRequestUriPrefix => [
        '{module:string}' => [
            '{id:int|!0}'  => [
                '__file__' => false
            ]
        ]
    ]
];
