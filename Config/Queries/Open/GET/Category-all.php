<?php
namespace Microservices\Config\Queries\Open\GET;

use Microservices\App\Constants;
use Microservices\App\DatabaseOpenCacheKey;

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
    'cacheKey' => DatabaseOpenCacheKey::$Category, 
    'responseLag' => [
        // No of Requests => Seconds Lag
        0 => 0, 
        // 2 => 10, 
    ], 
    'XSLT' => Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'XSLT' . DIRECTORY_SEPARATOR . 'Category.xls'
];
