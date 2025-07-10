<?php
/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Validation;

/**
 * Validator Trait
 * php version 8.3
 *
 * @category  Validator_Trait
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
trait ValidatorTrait
{
    /**
     * Validate string is alphanumeric
     *
     * @param string $v String
     *
     * @return bool|int
     */
    private function _isAlphanumeric(&$v): bool|int
    {
        return preg_match(pattern: '/^[a-z0-9 .\-]+$/i', subject: $v);
    }

    /**
     * Validate string is an email
     *
     * @param string $v email address
     *
     * @return mixed
     */
    private function _isEmail(&$v): mixed
    {
        return filter_var(value: $v, filter: FILTER_VALIDATE_EMAIL);
    }
}