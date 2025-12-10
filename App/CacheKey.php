<?php

/**
 * Server side Cache keys
 * php version 8.3
 *
 * @category  CacheServerKeys
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/**
 * Server side Cache keys - Required
 * php version 8.3
 *
 * @category  CacheServerKeys_Required
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class CacheKey
{
    /**
     * Get Client Key
     *
     * @param string $hostname Hostname
     *
     * @return string
     */
    public static function clientOpenToWeb(&$hostname): string
    {
        return "c:otw:{$hostname}";
    }

    /**
     * Get Client Key
     *
     * @param string $hostname Hostname
     *
     * @return string
     */
    public static function client($hostname): string
    {
        return "c:{$hostname}";
    }

    /**
     * Get Client User Key
     *
     * @param int    $cID Client Id
     * @param string $username Hostname
     *
     * @return string
     */
    public static function clientUser($cID, $username): string
    {
        return "cu:{$cID}:u:{$username}";
    }

    /**
     * Get Group Key
     *
     * @param int $gID Group Id
     *
     * @return string
     */
    public static function group($gID): string
    {
        return "g:{$gID}";
    }

    /**
     * Get Client CIDR Key
     *
     * @param int $cID Client Id
     *
     * @return string
     */
    public static function cCidr($cID): string
    {
        return "c:cidr:{$cID}";
    }

    /**
     * Get Group CIDR Key
     *
     * @param int $gID Group Id
     *
     * @return string
     */
    public static function gCidr($gID): string
    {
        return "g:cidr:{$gID}";
    }

    /**
     * Get User CIDR Key
     *
     * @param int $cID Client Id
     * @param int $uID User Id
     *
     * @return string
     */
    public static function uCidr($cID, $uID): string
    {
        return "u:cidr:{$cID}:{$uID}";
    }

    /**
     * Get Token Key
     *
     * @param string $token Token
     *
     * @return string
     */
    public static function token($token): string
    {
        return "t:{$token}";
    }

    /**
     * Get User Token Key
     *
     * @param int $uID User Id
     *
     * @return string
     */
    public static function userToken($uID): string
    {
        return "ut:{$uID}";
    }

    /**
     * Get User Token Key
     *
     * @param int $uID User Id
     *
     * @return string
     */
    public static function userSessionId($uID): string
    {
        return "u:sId:{$uID}";
    }
}
