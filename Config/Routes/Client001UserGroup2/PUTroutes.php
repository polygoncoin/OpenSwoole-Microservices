<?php
namespace Microservices\Config\Routes\Client001UserGroup1;

return array_merge (
    include $Constants::$DOC_ROOT . '/Config/Routes/Common/ClientDB/PUTroutes.php',
    include $Constants::$DOC_ROOT . '/Config/Routes/Common/ClientDB/Custom/PUTroutes.php'
);
