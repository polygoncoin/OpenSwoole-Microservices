<?php
namespace Microservices\Config\Queries\ClientDB\PATCH;

return array_merge(
    include Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Address.php',
    [
        '__CONFIG__' => [
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
