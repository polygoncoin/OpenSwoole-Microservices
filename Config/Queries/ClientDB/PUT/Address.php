<?php
namespace Microservices\Config\Queries\ClientDB\PUT;

return array_merge(
    include Constants::$DOC_ROOT . '/Config/Queries/ClientDB/Common/Address.php',
    [
        '__CONFIG__' => [// [{payload/uriParams}, key/index, {Constants::$REQUIRED}]
            ['payload', 'address', DatabaseDataTypes::$Default, Constants::$REQUIRED],
            ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey, Constants::$REQUIRED],
        ],
        '__SET__' => [
            'address' => ['payload', 'address']
        ],
        '__WHERE__' => [
            'is_deleted' => ['custom', 'No'],
            'id' => ['uriParams', 'id', DatabaseDataTypes::$PrimaryKey]
        ],
    ]
);
