<?php

/**
 * Test Case
 * php version 8.3
 *
 * @category  Test Case
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCase;

if (!defined('PRIVATE_GET')) {
	define('PRIVATE_GET', __DIR__ . DIRECTORY_SEPARATOR . 'Private' . DIRECTORY_SEPARATOR . 'GET');
	define('PRIVATE_POST', __DIR__ . DIRECTORY_SEPARATOR . 'Private' . DIRECTORY_SEPARATOR . 'POST');
	define('PRIVATE_PUT', __DIR__ . DIRECTORY_SEPARATOR . 'Private' . DIRECTORY_SEPARATOR . 'PUT');
	define('PRIVATE_PATCH', __DIR__ . DIRECTORY_SEPARATOR . 'Private' . DIRECTORY_SEPARATOR . 'PATCH');
	define('PRIVATE_DELETE', __DIR__ . DIRECTORY_SEPARATOR . 'Private' . DIRECTORY_SEPARATOR . 'DELETE');
}

if (!defined('PUBLIC_GET')) {
	define('PUBLIC_GET', __DIR__ . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'GET');
	define('PUBLIC_POST', __DIR__ . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'POST');
	define('PUBLIC_PUT', __DIR__ . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'PUT');
	define('PUBLIC_PATCH', __DIR__ . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'PATCH');
	define('PUBLIC_DELETE', __DIR__ . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'DELETE');
}

// $apiVersion = 'X-API-Version: v1.0.0';
$cacheControl = 'Cache-Control: no-cache';
// $contentType = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
// $contentType = 'Content-Type: multipart/form-data; charset=utf-8';
$contentType = 'Content-Type: text/plain; charset=utf-8';

$curlFile = __DIR__ . '/category.csv';

$defaultHeaderArr = [];
// $defaultHeaderArr[] = $apiVersion;
$defaultHeaderArr[] = $cacheControl;

$response = [];

$homeURL = 'http://127.0.0.1:9501';

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Reload.php';

$payload = [
	'username' => 'customer_1_group_1_user_1',
	'password' => 'shames11'
];
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Login.php';

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Route.php';

$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Config.php';
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Category.php';

$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'CategorySingle.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'CategoryOrderBy.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'RegistrationSingle.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'AddressSingle.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR
	. 'RegistrationWithAddressSingle.php';

$response[] = include PRIVATE_PUT . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_PUT . DIRECTORY_SEPARATOR . 'Address.php';

$response[] = include PRIVATE_PATCH . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_PATCH . DIRECTORY_SEPARATOR . 'Address.php';

$response[] = include PRIVATE_DELETE . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_DELETE . DIRECTORY_SEPARATOR . 'Address.php';

$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'CategoryConfig.php';

// Admin login
$payload = [
	'username' => 'customer_1_admin_1',
	'password' => 'shames11'
];
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Login.php';

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Route.php';

$response[] = include PRIVATE_DELETE . DIRECTORY_SEPARATOR . 'CategoryTruncate.php';

$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'CategorySingle.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'CategoryOrderBy.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'RegistrationSingle.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'AddressSingle.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';
$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR
	. 'RegistrationWithAddressSingle.php';

$response[] = include PRIVATE_PUT . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_PUT . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PRIVATE_PUT . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include PRIVATE_PATCH . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_PATCH . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PRIVATE_PATCH . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include PRIVATE_DELETE . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PRIVATE_DELETE . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PRIVATE_DELETE . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include PRIVATE_POST . DIRECTORY_SEPARATOR . 'CategoryConfig.php';

return $response;
