<?php
namespace Microservices\Config\Queries\Auth\ClientDB\Groups\AdminGroup\GET;

use Microservices\App\DatabaseCacheKey;
use Microservices\App\DatabaseDataTypes;

return [
    'countQuery' => "SELECT count(1) as `count` FROM `category` WHERE __WHERE__", 
    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__", 
    '__WHERE__' => [
        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ['column' => 'parent_id', 'fetchFrom' => 'custom', 'fetchFromValue' => 0], 
    ], 
    '__MODE__' => 'multipleRowFormat', 
    '__SUB-QUERY__' => [
        'sub' => [
            '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__", 
            '__WHERE__' => [
                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
                ['column' => 'parent_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:id'], 
            ], 
            '__MODE__' => 'multipleRowFormat', 
            '__SUB-QUERY__' => [
                'subsub' => [
                    '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__", 
                    '__WHERE__' => [
                        ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
                        ['column' => 'parent_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:sub:id'], 
                    ], 
                    '__MODE__' => 'multipleRowFormat', 
                    '__SUB-QUERY__' => [
                        'subsubsub' => [
                            '__QUERY__' => "SELECT * FROM `category` WHERE __WHERE__", 
                            '__WHERE__' => [
                                ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
                                ['column' => 'parent_id', 'fetchFrom' => 'sqlResults', 'fetchFromValue' => 'return:sub:subsub:id'], 
                            ], 
                            '__MODE__' => 'multipleRowFormat', 
                        ]
                    ]
                ]
            ], 
        ]
    ], 
    'useResultSet' => true, 
    'fetchFrom' => 'Master', 
    'cacheKey' => DatabaseCacheKey::$Category
];
