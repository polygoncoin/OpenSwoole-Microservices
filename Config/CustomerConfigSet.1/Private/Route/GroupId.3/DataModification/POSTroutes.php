<?php
namespace Microservices\Config\CustomerConfigSet_1\Private\Route\GroupId_3\DataModification;

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

return array_merge(
	require $this->httpObject->httpReqData['active']['commonRouteDir']
		. DIRECTORY_SEPARATOR . 'POSTroutes.php',
);
