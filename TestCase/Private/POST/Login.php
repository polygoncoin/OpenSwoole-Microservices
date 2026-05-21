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

use Microservices\App\Web;
use Microservices\App\Env;

$headerArr = $defaultHeaderArr;
$headerArr[] = $contentType;

$res = Web::trigger(
	homeURL: $homeURL,
	method: 'POST',
	route: '/login',
	header: $headerArr,
	payload: json_encode(value: $payload)
);


$token = null;
$sessionCookie = null;

if (isset($res['HttpResponse']['Headers']['Set-Cookie'])) {
	$sessionCookie = substr(
		$res['HttpResponse']['Headers']['Set-Cookie'],
		0,
		strpos(
			$res['HttpResponse']['Headers']['Set-Cookie'],
			'; '
		)
	);
} elseif (isset($res['HttpResponse']['ResponseBody']['Results']['Token'])) {
	$token = $res['HttpResponse']['ResponseBody']['Results']['Token'];
} elseif (isset($res['HttpResponse']['ResponseBody']['Results']['SessionId'])) {
	$sessionCookie = "PHPSESSID={$res['HttpResponse']['ResponseBody']['Results']['SessionId']}";
}

return $res;
