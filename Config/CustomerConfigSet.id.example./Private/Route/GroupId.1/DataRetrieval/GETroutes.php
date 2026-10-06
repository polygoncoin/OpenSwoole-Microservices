<?php
namespace Microservices\Config\CustomerConfigSet_id_example_\Private\Route\GroupId_1\DataRetrieval;

/**
 * API Route config
 * php version 8.3
 *
 * @category  API_Route_Config
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;

return array_merge(
	require $this->httpObject->httpReqData['current']['commonRouteDir']
		. DIRECTORY_SEPARATOR . 'GETroutes.php',
);
