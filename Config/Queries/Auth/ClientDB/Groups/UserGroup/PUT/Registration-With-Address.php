<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\UserGroup\PUT;

use Microservices\App\DatabaseDataTypes;

return [
    '__QUERY__' => "UPDATE `master_users` SET __SET__ WHERE __WHERE__", 
    '__SET__' => [
        ['column' => 'firstname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'firstname'], 
        ['column' => 'lastname', 'fetchFrom' => 'payload', 'fetchFromValue' => 'lastname'], 
        ['column' => 'email', 'fetchFrom' => 'payload', 'fetchFromValue' => 'email'], 
        ['column' => 'username', 'fetchFrom' => 'payload', 'fetchFromValue' => 'username'], 
        ['column' => 'password_hash', 'fetchFrom' => 'function', 'fetchFromValue' => function($sess) {
            return password_hash($sess['payload']['password'], PASSWORD_DEFAULT);
        }]
    ], 
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'user_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'id', 'dataType' => DatabaseDataTypes::$PrimaryKey]
    ], 
    '__SUB-QUERY__' => [
        'address' => [
            '__QUERY__' => "UPDATE `address` SET __SET__ WHERE __WHERE__", 
            '__SET__' => [
                ['column' => 'address', 'fetchFrom' => 'payload', 'fetchFromValue' => 'address']
            ], 
            '__WHERE__' => [
                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
                ['column' => 'id', 'fetchFrom' => 'payload', 'fetchFromValue' => 'id', 'dataType' => DatabaseDataTypes::$PrimaryKey], 
            ], 
        ]
    ], 
    '__VALIDATE__' => [
    [
      'fn' => '_primaryKeyExist', 
      'fnArgs' => [
                'table' => ['custom', 'master_users'], 
                'primary' => ['custom', 'user_id'], 
                'id' => ['uriParams', 'id']
            ], 
      'errorMessage' => 'Invalid registration id'
    ], 
  ], 
    'useHierarchy' => true, 
    'idempotentWindow' => 10
];
