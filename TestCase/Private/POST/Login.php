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

namespace Microservices\TestCase\Private\POST;

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\Web;

$token = Constant::$NULL;
$proceed = Constant::$FALSE;

$webResponse = Web::trigger(
	homeURL: $homeURL,
	httpRequestMethod: Constant::$POST,
	route: '/login',
	header: $publicHeaderArray,
	payload: json_encode(
		value: $payload
	)
);

if (isset($webResponse['HttpResponse']['ResponseBody']['Results']['Token'])) {
	$token = $webResponse['HttpResponse']['ResponseBody']['Results']['Token'];
	$privateHeaderArray = $publicHeaderArray;
	$privateHeaderArray[] = "Authorization: Bearer {$token}";
	$proceed = Constant::$TRUE;
} elseif (isset($webResponse['HttpResponse']['ResponseBody']['Results']['SessionId'])) {
	$privateHeaderArray = $publicHeaderArray;
	$proceed = Constant::$TRUE;
} else {
	$privateHeaderArray = $publicHeaderArray;
}

return $webResponse;
