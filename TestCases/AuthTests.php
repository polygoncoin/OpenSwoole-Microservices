<?php
/**
 * TestCases
 * php version 8.3
 *
 * @category  TestCases
 * @package   Openswoole-Microservices
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

$response[] = include GET . DIRECTORY_SEPARATOR . 'Routes.php';

$response[] = include POST . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include POST . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include POST . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include POST . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include GET . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'CategorySingle.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'CategoryOrderBy.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'RegistrationSingle.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'AddressSingle.php';
$response[] = include GET . DIRECTORY_SEPARATOR .
    'RegistrationWithAddressSingle.php';

$response[] = include PUT . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PUT . DIRECTORY_SEPARATOR . 'Address.php';

$response[] = include PATCH . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PATCH . DIRECTORY_SEPARATOR . 'Address.php';

$response[] = include DELETE . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include DELETE . DIRECTORY_SEPARATOR . 'Address.php';

$response[] = include POST . DIRECTORY_SEPARATOR . 'CategoryConfig.php';

// Admin login
$payload = [
    'username' => 'client_1_admin_1',
    'password' => 'shames11'
];
$response[] = include POST . DIRECTORY_SEPARATOR . 'Login.php';

$response[] = include GET . DIRECTORY_SEPARATOR . 'Routes.php';

$response[] = include DELETE . DIRECTORY_SEPARATOR . 'CategoryTruncate.php';

$response[] = include POST . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include POST . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include POST . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include POST . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include GET . DIRECTORY_SEPARATOR . 'Category.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'CategorySingle.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'CategoryOrderBy.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'RegistrationSingle.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'AddressSingle.php';
$response[] = include GET . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';
$response[] = include GET . DIRECTORY_SEPARATOR .
    'RegistrationWithAddressSingle.php';

$response[] = include PUT . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PUT . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PUT . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include PATCH . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include PATCH . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include PATCH . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include DELETE . DIRECTORY_SEPARATOR . 'Registration.php';
$response[] = include DELETE . DIRECTORY_SEPARATOR . 'Address.php';
$response[] = include DELETE . DIRECTORY_SEPARATOR . 'RegistrationWithAddress.php';

$response[] = include POST . DIRECTORY_SEPARATOR . 'CategoryConfig.php';

return '<pre>'.print_r(value: $response, return: true);
