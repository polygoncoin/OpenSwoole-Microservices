<?php
/**
 * TestCases
 * php version 8.3
 *
 * @category  TestCases
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\TestCases;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestFunctions.php';

$header = [];
$response = [];

$homeURL = 'http://127.0.0.1:9501';

$response[] = include GET . DIRECTORY_SEPARATOR . 'Reload.php';

// Client login
$payload = [
    'username' => 'client_1_group_1_user_1',
    'password' => 'shames11'
];
$response[] = include POST . DIRECTORY_SEPARATOR . 'Login.php';

$response[] = include POST . DIRECTORY_SEPARATOR . 'SupplementTest.php';

echo '<pre>';
print_r(value: $response);
