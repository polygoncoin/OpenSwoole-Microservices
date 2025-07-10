<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\DELETE;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;

return array_merge(
    include Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Queries' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'ClientDB' . DIRECTORY_SEPARATOR . 'Common' . DIRECTORY_SEPARATOR . 'Registration.php', 
    [
        '__SET__' => [
            ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes']
        ], 
        '__WHERE__' => [
            ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
            ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id', 'dataType' => DatabaseDataTypes::$PrimaryKey]
        ], 
    ]
);

