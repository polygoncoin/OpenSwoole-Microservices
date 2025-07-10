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
    public static function client(&$hostname): string
    {
        return "c:{$hostname}";
    }

    /**
     * Get Client User Key
     *
     * @param int    $clientId Client Id
     * @param string $username Hostname
     *
     * @return string
     */
    public static function clientUser(&$clientId, &$username): string
    {
        return "cu:{$clientId}:u:{$username}";
    }

    /**
     * Get Group Key
     *
     * @param int $groupId Group Id
     *
     * @return string
     */
    public static function group(&$groupId): string
    {
        return "g:{$groupId}";
    }

    /**
     * Get Group CIDR Key
     *
     * @param int $groupId Group Id
     *
     * @return string
     */
    public static function cidr(&$groupId): string
    {
        return "cidr:{$groupId}";
    }

    /**
     * Get Token Key
     *
     * @param string $token Token
     *
     * @return string
     */
    public static function token(&$token): string
    {
        return "t:{$token}";
    }

    /**
     * Get User Token Key
     *
     * @param int $userId User Id
     *
     * @return string
     */
    public static function userToken(&$userId): string
    {
        return "ut:{$userId}";
    }
}
