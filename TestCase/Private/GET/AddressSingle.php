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

$headerArray = $defaultHeaderArray;
$proceed = false;

if (
	isset($token)
	&& $token !== Constant::$NULL
) {
	$headerArray[] = "Authorization: Bearer {$token}";
	$proceed = true;
}
if (
	isset($sessionCookie)
	&& $sessionCookie !== Constant::$NULL
) {
	$headerArray[] = "Cookie: {$sessionCookie}";
	$proceed = true;
}

if (isset($proceed)) {
	return Web::trigger(
		homeURL: $homeURL,
		httpRequestMethod: Constant::$GET,
		route: '/address/1',
		header: $headerArray,
		payload: ''
	);
}
