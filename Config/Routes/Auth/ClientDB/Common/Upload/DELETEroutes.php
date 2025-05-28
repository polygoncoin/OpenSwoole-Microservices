<?php
namespace Microservices\Config\Routes\Auth\ClientDB\Common\Client;

return [
    $Env::$uploadRequestUriPrefix => [
        '{module:string}' => [
            '{id:int|!0}'  => [
                '__FILE__' => false
            ]
        ]
    ]
];
