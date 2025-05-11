<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\GET;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'all' => [
        'countQuery' => "SELECT count(1) as `count` FROM `{$Env::$clients}` WHERE __WHERE__",
        '__QUERY__' => "SELECT * FROM `{$Env::$clients}` WHERE __WHERE__ ORDER BY client_id ASC",
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No']
            ],
        '__MODE__' => 'multipleRowFormat'
    ],
    'single' => [
        '__QUERY__' => "SELECT * FROM `{$Env::$clients}` WHERE __WHERE__",
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No'],
            'client_id' => ['uriParams','client_id']
        ],
        '__MODE__' => 'singleRowFormat'
    ],
][isset($this->c->httpRequest->session['uriParams']['client_id'])?'single':'all'];
