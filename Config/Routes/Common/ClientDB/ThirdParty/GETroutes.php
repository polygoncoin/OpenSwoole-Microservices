<?php
namespace Microservices\Config\Routes\Common\ClientDB\ThirdParty;

return [
    $Env::$thirdPartyRequestUriPrefix => [
        '{thirdParty:string}' => [
            '__file__' => false
        ]
    ]
];
