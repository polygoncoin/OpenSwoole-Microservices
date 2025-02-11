<?php
namespace Microservices\Config\Queries\GlobalDB\GET;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'all' => [
        'query' => "SELECT * FROM `{$Env::$groups}` WHERE __WHERE__ ORDER BY group_id ASC",
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No'],
        ],
        'mode' => 'multipleRowFormat'//Multiple rows returned.
    ],
    'single' => [
        'query' => "SELECT * FROM `{$Env::$groups}` WHERE __WHERE__",
        '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
            ['uriParams', 'group_id', DatabaseDataTypes::$INT, Constants::$REQUIRED],
        ],
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No'],
            'group_id' => ['uriParams','group_id']
        ],
        'mode' => 'singleRowFormat'//Single row returned.
    ]
][isset($this->c->httpRequest->session['uriParams']['group_id'])?'single':'all'];
