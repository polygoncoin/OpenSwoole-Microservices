<?php
namespace Microservices\Config\Routes\Common\ClientDB\Client;

use Microservices\App\Constants;

return [
    'upload' => [
        '{module:string}' => [
            '{id:int|!0}'  => [
                '__file__' => false
            ]
        ]
    ]
];
