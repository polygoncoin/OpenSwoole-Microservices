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

switch (Env::$authMode) {
	case 'Token':
		if (isset($res['response']['responseBody']['Results']['Token'])) {
			$token = $res['response']['responseBody']['Results']['Token'];
		}
		break;
	case 'Session':
		if (isset($res['response']['responseHeaderArr']['Set-Cookie'])) {
			$sessionCookie = substr(
				$res['response']['responseHeaderArr']['Set-Cookie'],
				0,
				strpos(
					$res['response']['responseHeaderArr']['Set-Cookie'],
					'; '
				)
			);
		}
		break;
}

return $res;
