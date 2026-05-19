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

use Microservices\App\Web;

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

$defaultHeaderArr = [];
// $defaultHeaderArr[] = $apiVersion;
$defaultHeaderArr[] = $cacheControl;

$response = [];

$homeURL = 'http://127.0.0.1:9501';

$response[] = include PRIVATE_GET . DIRECTORY_SEPARATOR . 'Reload.php';

$paramArr = [
	'Payload' => [
		'firstname' => 'Ramesh1',
		'lastname' => 'Jangid',
		'email' => 'ramesh@test.com',
		'username' => 'test',
		'password' => 'shames11',
		'address' => [
			'address' => 'A-203'
		]
	]
];

$payload = '<?xml version="1.0" encoding="UTF-8" ?>';
Web::genXmlPayload(xmlParamArr: $paramArr, payload: $payload);

$response[] = Web::trigger(
	homeURL: $homeURL,
	method: 'POST',
	route: '/registration-with-address'
		. '&iRepresentation=XML&oRepresentation=XML',
	header: $defaultHeaderArr,
	payload: $payload
);

return $response;
