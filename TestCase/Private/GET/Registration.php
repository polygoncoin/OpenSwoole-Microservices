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
$proceed = false;
switch (Env::$authMode) {
	case 'Token':
		if (
			isset($token)
			&& $token !== null
		) {
			$headerArr[] = "Authorization: Bearer {$token}";
			$proceed = true;
		}
		break;
	case 'Session':
		if (
			isset($sessionCookie)
			&& $sessionCookie !== null
		) {
			$headerArr[] = "Cookie: {$sessionCookie}";
			$proceed = true;
		}
		break;
}

if (isset($proceed)) {
	return Web::trigger(
		homeURL: $homeURL,
		method: 'GET',
		route: '/registration',
		header: $headerArr,
		payload: ''
	);
}
