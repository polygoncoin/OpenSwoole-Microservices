<?php

/**
 * Test Case
 * php version 8.3
 * 
 * @category  Test Case
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCase;

use Microservices\App\Constant;
use Microservices\App\Web;

if (!defined('PRIVATE_GET')) {
	define('PRIVATE_DIRECTORY', __DIR__ . DIRECTORY_SEPARATOR . 'Private');
	define('PRIVATE_GET', PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$GET);
	define('PRIVATE_QUERY', PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$QUERY);
	define('PRIVATE_POST', PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$POST);
	define('PRIVATE_PUT', PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$PUT);
	define('PRIVATE_PATCH', PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$PATCH);
	define('PRIVATE_DELETE', PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$DELETE);
}

// $apiVersion = 'X-API-Version: v1.0.0';
$cacheControl = 'Cache-Control: no-cache';
// $contentType = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
// $contentType = 'Content-Type: multipart/form-data; charset=utf-8';
$contentType = 'Content-Type: text/plain; charset=utf-8';

$defaultHeaderArray = [];
// $defaultHeaderArray[] = $apiVersion;
$defaultHeaderArray[] = $cacheControl;

$response = [];

if (defined('__MODE__')) {
	$homeURL = 'http://127.0.0.1:9501';
} else {
	$homeURL = 'http://127.0.0.1:9501';
}

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Reload.php';

// Customer login
$payload = [
	'username' => 'customer_1_group_1_user_1',
	'password' => 'shames11'
];
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Login.php';

$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'SupplementTest.php';

return $response;
