<?php
namespace Microservices\Config\Queries\Auth\ClientDB\GET;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "SELECT * FROM `master_users` WHERE __WHERE__",
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'],
        ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id']
    ],
    '__MODE__' => 'singleRowFormat'
];
