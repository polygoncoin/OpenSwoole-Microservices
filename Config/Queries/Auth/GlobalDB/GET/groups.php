<?php
namespace Microservices\Config\Queries\Auth\GlobalDB\GET;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;

return [
    'all' => [
        '__QUERY__' => "SELECT * FROM `{$Env::$groups}` WHERE __WHERE__ ORDER BY group_id ASC", 
        '__WHERE__' => [
            ['column' => 'is_approved', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes'], 
            ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
            ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
        ], 
        '__MODE__' => 'multipleRowFormat'
    ], 
    'single' => [
        '__QUERY__' => "SELECT * FROM `{$Env::$groups}` WHERE __WHERE__", 
        '__WHERE__' => [
            ['column' => 'is_approved', 'fetchFrom' => 'custom', 'fetchFromValue' => 'Yes'], 
            ['column' => 'is_disabled', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
            ['column' => 'is_deleted', 'fetchFrom' => 'custom', 'fetchFromValue' => 'No'], 
            ['column' => 'group_id', 'fetchFrom' => 'uriParams', 'fetchFromValue' => 'group_id'], 
        ], 
        '__MODE__' => 'singleRowFormat'
    ]
][isset($this->_c->req->sess['uriParams']['group_id'])?'single':'all'];
