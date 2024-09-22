<?php
namespace Microservices\Config\Queries\GlobalDB\GET;

use Microservices\App\Constants;

return [
    'all' => [
        'query' => "SELECT * FROM `{$Env::$connections}` WHERE __WHERE__ ORDER BY connection_id ASC",
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No'],
        ],
        'mode' => 'multipleRowFormat'//Multiple rows returned.
    ],
    'single' => [
        'query' => "SELECT * FROM `{$Env::$connections}` WHERE __WHERE__",
        '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
            ['uriParams', 'connection_id', Constants::$REQUIRED],
        ],
        '__WHERE__' => [
            'is_approved' => ['custom', 'Yes'],
            'is_disabled' => ['custom', 'No'],
            'is_deleted' => ['custom', 'No'],
            'connection_id' => ['uriParams','connection_id']
        ],
        'mode' => 'singleRowFormat'//Single row returned.
    ]
][isset($this->c->httpRequest->input['uriParams']['connection_id'])?'single':'all'];
