<?php
namespace Microservices\Config\Routes\Client001UserGroup1;

use Microservices\App\Constants;

return array_merge(
    include Constants::$DOC_ROOT . '/Config/Routes/Common/ClientDB/POSTroutes.php',
    include Constants::$DOC_ROOT . '/Config/Routes/Common/ClientDB/ThirdParty/POSTroutes.php',
    include Constants::$DOC_ROOT . '/Config/Routes/Common/ClientDB/Upload/POSTroutes.php',
);
