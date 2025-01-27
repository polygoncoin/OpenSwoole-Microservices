<?php
namespace Microservices\Config\Queries\GlobalDB\GET;

return [
    'all' => [
        'countQuery' => "SELECT count(1) as `count` FROM `{$Env::$clients}` WHERE __WHERE__",
        'query' => "SELECT * FROM `{$Env::$clients}` WHERE __WHERE__ ORDER BY client_id ASC",
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No']
            ],
        'mode' => 'multipleRowFormat'//Multiple rows returned.
    ],
    'single' => [
        'query' => "SELECT * FROM `{$Env::$clients}` WHERE __WHERE__",
        '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
            ['uriParams', 'client_id', $Constants::$REQUIRED],
        ],
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No'],
            'client_id' => ['uriParams','client_id']
        ],
        'mode' => 'singleRowFormat'//Single row returned.
    ],
][isset($this->c->httpRequest->session['uriParams']['client_id'])?'single':'all'];
