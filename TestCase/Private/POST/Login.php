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
use Microservices\App\Env;
use Microservices\App\Web;

$webResponse = Web::trigger(
	homeURL: $homeURL,
	httpRequestMethod: Constant::$POST,
	route: '/login',
	header: $publicHeaderArray,
	payload: json_encode(
		value: $payload
	)
);

$token = Constant::$NULL;
$sessionCookie = Constant::$NULL;
$proceed = Constant::$FALSE;

if (isset($webResponse['HttpResponse']['Headers']['Set-Cookie'])) {
	$sessionCookie = substr(
		string: $webResponse['HttpResponse']['Headers']['Set-Cookie'],
		offset: 0,
		length: strpos(
			haystack: $webResponse['HttpResponse']['Headers']['Set-Cookie'],
			needle: '; '
		)
	);
} elseif (isset($webResponse['HttpResponse']['ResponseBody']['Results']['Token'])) {
	$token = $webResponse['HttpResponse']['ResponseBody']['Results']['Token'];
} elseif (isset($webResponse['HttpResponse']['ResponseBody']['Results']['SessionId'])) {
	$sessionCookie = "PHPSESSID={$webResponse['HttpResponse']['ResponseBody']['Results']['SessionId']}";
}

if (
	$token !== Constant::$NULL
	|| $sessionCookie !== Constant::$NULL
) {
	$privateHeaderArray = $publicHeaderArray;
	switch (!Constant::$NULL) {
		case $token:
			$privateHeaderArray[] = "Authorization: Bearer {$token}";
			break;
		case $sessionCookie:
			$privateHeaderArray[] = "Cookie: {$sessionCookie}";
			break;
	}
	$proceed = Constant::$TRUE;
}

return $webResponse;
