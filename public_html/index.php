<?php

/**
 * Index
 * php version 8.3
 *
 * @category  Start
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

define ('__MODE__', 'private_api'); // private_api/private_web/public
// define ('__MODE__', 'private_web'); // private_api/private_web/public
// define ('__MODE__', 'public'); // private_api/private_web/public

switch (__MODE__) {
	case 'public':
		$DOMAIN_NAME = 'customer001.localhost'; // Public mode
		include __DIR__ . DIRECTORY_SEPARATOR . 'session_index.php';
		break;
	case 'private_web':
		$DOMAIN_NAME = 'web.customer001.localhost'; // Private Session mode
		include __DIR__ . DIRECTORY_SEPARATOR . 'session_index.php';
		break;
	case 'private_api':
		$DOMAIN_NAME = 'api.customer001.localhost'; // Private Token mode
		include __DIR__ . DIRECTORY_SEPARATOR . 'token_index.php';
		break;
}