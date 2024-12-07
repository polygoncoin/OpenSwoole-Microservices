<?php
namespace Microservices\Config\Queries\ClientDB\PUT;

return array_merge(
    include Constants::$DOC_ROOT . '/Config/Queries/ClientDB/Common/Registration.php',
    [
        '__CONFIG__' => [// [{payload/uriParams}, key/index, {$Constants::$REQUIRED}]
            ['payload', 'firstname', $Constants::$REQUIRED],
            ['payload', 'lastname', $Constants::$REQUIRED],
            ['payload', 'email', $Constants::$REQUIRED],
            ['uriParams', 'id', $Constants::$REQUIRED],
        ],
        '__SET__' => [
            'firstname' => ['payload', 'firstname'],
            'lastname' => ['payload', 'lastname'],
            'email' => ['payload', 'email']
        ],
        '__WHERE__' => [
            'is_deleted' => ['custom', 'No'],
            'id' => ['uriParams', 'id']
        ],
    ]
);
