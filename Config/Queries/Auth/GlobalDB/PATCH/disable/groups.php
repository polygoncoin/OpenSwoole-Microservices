<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\PATCH\disable;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    '__QUERY__' => "UPDATE `{$Env::$groups}` SET __SET__ WHERE __WHERE__", 
    '__SET__' => [
        ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes'], 
        ['column' => 'updated_by', 'fetchFrom' => 'userDetails', 'fetchFromValue' => 'user_id'], 
        ['column' => 'updated_on', 'fetchFrom' => 'custom', 'fetchFromValue' => date('Y-m-d H:i:s')]
    ], 
    '__WHERE__' => [
        ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'group_id', 'fetchFrom' => 'payload', 'fetchFromValue' => 'group_id', 'dataType' => DatabaseDataTypes::$INT]
    ], 
    '__VALIDATE__' => [
    [
      'fn' => '_primaryKeyExist', 
      'fnArgs' => [
                'table' => ['custom', Env::$groups], 
                'primary' => ['custom', 'group_id'], 
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT]
            ], 
      'errorMessage' => 'Invalid Group Id'
    ], 
    [
      'fn' => '_checkColumnValueExist', 
      'fnArgs' => [
                'table' => ['custom', Env::$groups], 
                'column' => ['custom', 'is_deleted'], 
                'columnValue' => ['custom', 'No'], 
                'primary' => ['custom', 'group_id'], 
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT], 
            ], 
      'errorMessage' => 'Record is deleted'
    ], 
    [
      'fn' => '_checkColumnValueExist', 
      'fnArgs' => [
                'table' => ['custom', Env::$groups], 
                'column' => ['custom', 'is_disabled'], 
                'columnValue' => ['custom', 'No'], 
                'primary' => ['custom', 'group_id'], 
                'id' => ['payload', 'group_id', DatabaseDataTypes::$INT], 
            ], 
      'errorMessage' => 'Record is already disabled'
    ]
  ]
];
