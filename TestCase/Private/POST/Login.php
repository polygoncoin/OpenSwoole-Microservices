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

$apiToken = Constant::$NULL;
$proceed = Constant::$FALSE;

$webResponse = Web::trigger(
	homeURL: $homeURL,
	httpRequestMethod: Constant::$POST,
	route: '/login.json',
	header: $publicHeaderArray,
	payload: json_encode(
		value: $payload
	)
);

$privateHeaderArray = $publicHeaderArray;

if (
	isset($webResponse['HttpResponse']['ResponseBody']['Results']['authMode'])
	&& isset($webResponse['HttpResponse']['ResponseBody']['Results']['authId'])
) {
	if ($webResponse['HttpResponse']['ResponseBody']['Results']['authMode'] === 'API Token') {
		$apiToken = $webResponse['HttpResponse']['ResponseBody']['Results']['authId'];
		$privateHeaderArray[] = "Authorization: Bearer {$apiToken}";
	}
	$proceed = Constant::$TRUE;
}

return $webResponse;
