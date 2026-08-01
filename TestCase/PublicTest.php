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

if (!defined('PUBLIC_GET')) {
	define('PUBLIC_DIRECTORY', __DIR__ . DIRECTORY_SEPARATOR . 'Public');
	define('PUBLIC_GET', PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$GET);
	define('PUBLIC_QUERY', PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$QUERY);
	define('PUBLIC_POST', PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$POST);
	define('PUBLIC_PUT', PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$PUT);
	define('PUBLIC_PATCH', PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$PATCH);
	define('PUBLIC_DELETE', PUBLIC_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$DELETE);
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

$homeURL = 'http://127.0.0.1:9501';

if (!defined('PRIVATE_GET')) {
	define('PRIVATE_DIRECTORY', __DIR__ . DIRECTORY_SEPARATOR . 'Private');
	define('PRIVATE_GET', PRIVATE_DIRECTORY . DIRECTORY_SEPARATOR . Constant::$GET);
}

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Reload.php';

$response[] = include PUBLIC_POST . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PUBLIC_POST . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';
$response[] = include PUBLIC_POST . DIRECTORY_SEPARATOR . 'Address.php';

$response[] = include PUBLIC_GET . DIRECTORY_SEPARATOR . 'CategorySingle.php';
$response[] = include PUBLIC_GET . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include PUBLIC_GET . DIRECTORY_SEPARATOR . 'CategoryOrderBy.php';

$response[] = include PUBLIC_QUERY . DIRECTORY_SEPARATOR . 'Category.php';

return $response;
