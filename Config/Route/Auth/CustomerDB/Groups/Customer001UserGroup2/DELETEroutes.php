<?php

/**
 * API Route config
 * php version 8.3
 *
 * @category  API_Route_Config
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Config\Route\Auth\CustomerDB\Groups\Customer001UserGroup1;

use Microservices\App\Constant;

return require Constant::$AUTH_ROUTES_DIR
	. DIRECTORY_SEPARATOR . 'CustomerDB'
	. DIRECTORY_SEPARATOR . 'Common'
	. DIRECTORY_SEPARATOR . 'DELETEroutes.php';
